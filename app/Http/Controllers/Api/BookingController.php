<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * The booking wizard's "Continue" step.
 *
 * Saved as one customer, one patient and one appointment, because a booking is
 * all three at once and a half-written booking is worse than none.
 *
 * This runs here rather than in the Next.js app for the same reason as
 * PeopleController: the database is on this host, and a Vercel function cannot
 * reach it.
 */
class BookingController extends Controller
{
    private const ADDRESS_SEPARATOR = ' ~ ';

    private const ADDRESS_KEYS = [
        'address1',
        'address2',
        'suburb',
        'city',
        'state',
        'zipCode',
        'country',
    ];

    private const GENDERS = ['Male', 'Female', 'Other'];

    public function store(Request $request): JsonResponse
    {
        $bookingFor = $request->input('booking_for') === 'other' ? 'other' : 'self';

        $firstName = trim((string) $request->input('first_name'));
        $lastName  = trim((string) $request->input('last_name'));

        if ($firstName === '' || $lastName === '') {
            return response()->json(['message' => 'First name and last name are required.'], 422);
        }

        $gender = trim((string) $request->input('gender'));
        if (!in_array($gender, self::GENDERS, true)) {
            return response()->json([
                'message' => 'Gender must be one of: ' . implode(', ', self::GENDERS) . '.',
            ], 422);
        }

        $dob = $this->normaliseDate($request->input('dob'));
        if ($dob === null) {
            return response()->json(['message' => 'A valid date of birth is required.'], 422);
        }

        // The slot is chosen in step 1, so by the time the user reaches Continue
        // it is always present.
        $appointmentDate = $this->normaliseDate($request->input('appointment_date'));
        if ($appointmentDate === null) {
            return response()->json(['message' => 'A valid appointment date is required.'], 422);
        }

        $startTime = $this->normaliseTime($request->input('start_time'));
        if ($startTime === null) {
            return response()->json(['message' => 'A valid appointment time is required.'], 422);
        }

        // A customer's email is the unique key they are matched on, so a
        // self-booking must have one. A relative or friend often has no email of
        // their own, so it stays optional on that path.
        $email = strtolower(trim((string) $request->input('email')));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'Please provide a valid email address.'], 422);
        }
        if ($bookingFor === 'self' && $email === '') {
            return response()->json(['message' => 'A valid email address is required.'], 422);
        }

        $serviceName = trim((string) $request->input('service_name'));

        try {
            return DB::transaction(function () use (
                $request, $bookingFor, $firstName, $lastName, $gender, $dob,
                $appointmentDate, $startTime, $email, $serviceName
            ) {
                $serviceId = $this->resolveServiceId($serviceName);

                $attributes = [
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'gender'     => $gender,
                    'dob'        => $dob,
                    'email'      => $email !== '' ? $email : null,
                    'phone'      => $this->nullableString($request->input('phone'), 50),
                    'address'    => $this->joinAddress($request),
                ];

                if (Schema::hasColumn('patients', 'title')) {
                    // The booking is for this person, so the same title the
                    // customer row gets is stored against the patient too.
                    $attributes['title'] = $this->nullableString($request->input('title'), 255);
                }

                if ($bookingFor === 'other') {
                    return $this->bookForOther($request, $attributes, $serviceId, $appointmentDate, $startTime);
                }

                return $this->bookForSelf($request, $attributes, $email, $serviceId, $appointmentDate, $startTime);
            });
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'We could not save your booking. Please try again.',
            ], 500);
        }
    }

    /**
     * Booking for a relative or friend.
     *
     * The person becomes a `patient` and never a `customer`, and they are attached
     * to the signed-in customer so they show up in that account's "My People". A
     * guest booking for someone else simply has no booker yet, which leaves the
     * link null rather than inventing a customer account for the relative.
     */
    private function bookForOther(
        Request $request,
        array $patientAttributes,
        ?int $serviceId,
        string $appointmentDate,
        string $startTime
    ): JsonResponse {
        $bookedByEmail = strtolower(trim((string) $request->input('booked_by_email')));
        $bookerCustomerId = null;

        if ($bookedByEmail !== '' && filter_var($bookedByEmail, FILTER_VALIDATE_EMAIL)) {
            $booker = Customer::where('email', $bookedByEmail)->first();
            $bookerCustomerId = $booker?->id;
        }

        $existingPatientId = $this->findExistingPatient($bookerCustomerId, $patientAttributes);
        $patientExisted = $existingPatientId !== null;

        if ($patientExisted) {
            Patient::where('id', $existingPatientId)->update($patientAttributes);
            $patientId = $existingPatientId;
        } else {
            $patient = new Patient($patientAttributes);
            $patient->customer_id = $bookerCustomerId;
            $patient->status = 'active';
            $patient->save();
            $patientId = $patient->id;
        }

        [$appointment, $appointmentExisted] = $this->recordAppointment(
            $patientId, $serviceId, $appointmentDate, $startTime, $request->input('notes')
        );

        $patient = Patient::find($patientId);

        return response()->json([
            'message' => $patientExisted
                ? 'Patient updated successfully.'
                : 'Patient created successfully.',
            'booking_for' => 'other',
            'customer_touched' => false,
            'patient_created' => !$patientExisted,
            'patient' => $this->presentPatient($patient),
            'appointment_created' => !$appointmentExisted,
            'appointment' => $appointment,
        ]);
    }

    /** Booking for the signed-in customer themselves. */
    private function bookForSelf(
        Request $request,
        array $patientAttributes,
        string $email,
        ?int $serviceId,
        string $appointmentDate,
        string $startTime
    ): JsonResponse {
        $customer = Customer::where('email', $email)->first();
        $customerAlreadyExisted = $customer !== null;

        $customerAttributes = [
            'first_name'     => $patientAttributes['first_name'],
            'last_name'      => $patientAttributes['last_name'],
            'gender'         => $patientAttributes['gender'],
            'dob'            => $patientAttributes['dob'],
            'title'          => $this->nullableString($request->input('title'), 255),
            'phone'          => $patientAttributes['phone'],
            'address_line_1' => $this->nullableString($request->input('address_line_1'), 255),
            'address_line_2' => $this->nullableString($request->input('address_line_2'), 255),
            'suburb'         => $this->nullableString($request->input('suburb'), 255),
            'city'           => $this->nullableString($request->input('city'), 255),
            'state'          => $this->nullableString($request->input('state'), 255),
            'zip_code'       => $this->nullableString($request->input('zip_code'), 50),
            'country'        => $this->nullableString($request->input('country'), 255),
        ];

        if ($customer) {
            $customer->update($customerAttributes);
        } else {
            $customer = new Customer($customerAttributes);
            $customer->email = $email;
            $customer->customer_code = Customer::generateNextCustomerCode();
            $customer->status = 'active';
            $customer->save();
        }

        $existingPatientId = $this->findExistingPatient($customer->id, $patientAttributes);
        $patientExisted = $existingPatientId !== null;

        if ($patientExisted) {
            Patient::where('id', $existingPatientId)->update($patientAttributes + ['customer_id' => $customer->id]);
            $patientId = $existingPatientId;
        } else {
            $patient = new Patient($patientAttributes);
            $patient->customer_id = $customer->id;
            $patient->status = 'active';
            $patient->save();
            $patientId = $patient->id;
        }

        [$appointment, $appointmentExisted] = $this->recordAppointment(
            $patientId, $serviceId, $appointmentDate, $startTime, $request->input('notes')
        );

        return response()->json([
            'message' => $patientExisted
                ? 'Customer and patient updated successfully.'
                : 'Customer and patient created successfully.',
            'booking_for' => 'self',
            'created' => !$customerAlreadyExisted,
            'customer_touched' => true,
            'patient_created' => !$patientExisted,
            'appointment_created' => !$appointmentExisted,
            'customer' => $this->presentCustomer($customer),
            'patient' => $this->presentPatient(Patient::find($patientId)),
            'appointment' => $appointment,
        ]);
    }

    /**
     * Lists a customer's appointments, newest first, optionally narrowed to one
     * patient — which is what the person detail page shows.
     *
     * Scoped through the patients' customer_id rather than by appointment, so one
     * account can never read another account's bookings.
     */
    public function index(Request $request): JsonResponse
    {
        $email = strtolower(trim((string) $request->query('customer_email', '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'A valid email address is required.'], 422);
        }

        $customer = Customer::where('email', $email)->first();

        // No customer record means no appointments — a guest, or someone who has
        // not booked. That is an empty list, not an error.
        if (!$customer) {
            return response()->json(['appointments' => [], 'customer_found' => false]);
        }

        $query = Appointment::query()
            ->whereHas('patient', fn ($q) => $q->where('customer_id', $customer->id));

        $patientId = $request->query('patient_id');
        if (is_numeric($patientId)) {
            $query->where('patient_id', (int) $patientId);
        }

        $appointments = $query->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Appointment $a) => $this->presentAppointmentDetail($a))
            ->values();

        return response()->json([
            'appointments'    => $appointments,
            'customer_found'  => true,
        ]);
    }

    /** Writes the appointment, or updates the existing one for the same patient,
     * date and time.
     *
     * `appointments` has no unique key on those three columns, so re-submitting
     * the wizard would otherwise create a duplicate booking. Checking first makes
     * a double submit update one row rather than two.
     */
    private function recordAppointment(
        int $patientId,
        ?int $serviceId,
        string $appointmentDate,
        string $startTime,
        $notes
    ): array {
        $existing = Appointment::where('patient_id', $patientId)
            ->where('appointment_date', $appointmentDate)
            ->where('start_time', $startTime)
            ->first();

        $attributes = [
            'service_id'       => $serviceId,
            'appointment_date' => $appointmentDate,
            'start_time'       => $startTime,
            'notes'            => $this->nullableString($notes, 2000),
        ];

        if ($existing) {
            $existing->update($attributes);
            $appointment = $existing;
            $existed = true;
        } else {
            $appointment = new Appointment($attributes);
            $appointment->patient_id = $patientId;
            // These three are enums, not free text, so only the listed values are
            // accepted: status 'Scheduled'|'Confirmed'|'In-Progress'|'Completed'|
            // 'Cancelled'|'No-Show', payment_status 'Paid'|'Pending'|'Partial',
            // source 'web_frontend'|'admin_portal'|'phone'.
            $appointment->status = 'Scheduled';
            $appointment->payment_status = 'Pending';
            $appointment->source = 'web_frontend';
            $appointment->save();
            $existed = false;
        }

        return [$this->presentAppointment($appointment), $existed];
    }

    /**
     * Matches a person on identity rather than on an id the client supplied, so
     * booking the same relative twice reuses their row instead of creating a
     * second patient for them.
     */
    private function findExistingPatient(?int $customerId, array $attributes): ?int
    {
        $query = Patient::where('first_name', $attributes['first_name'])
            ->where('last_name', $attributes['last_name']);

        if (!empty($attributes['dob'])) {
            $query->where('dob', $attributes['dob']);
        }

        if ($customerId !== null) {
            $query->where('customer_id', $customerId);
        }

        return $query->orderBy('id')->value('id');
    }

    /**
     * Resolves the service so the appointment points at the right row.
     *
     * The frontend names a scan by its title, which lands in either service_name
     * or title depending on the category. If that is ambiguous, service_id is left
     * null rather than guessed — the column is nullable, and clinic_id and
     * staff_id are assigned by staff later.
     */
    private function resolveServiceId(string $serviceName): ?int
    {
        if ($serviceName === '') {
            return null;
        }

        $rows = DB::table('services')
            ->where('service_name', $serviceName)
            ->orWhere('title', $serviceName)
            ->limit(2)
            ->get();

        return $rows->count() === 1 ? (int) $rows->first()->id : null;
    }

    private function presentCustomer(Customer $customer): array
    {
        return [
            'id'            => (int) $customer->id,
            'customer_code' => $customer->customer_code,
            'first_name'    => $customer->first_name,
            'last_name'     => $customer->last_name,
            'email'         => $customer->email,
            'gender'        => $customer->gender,
            'title'         => $customer->title,
            'dob'           => $customer->dob?->format('Y-m-d'),
            'phone'         => $customer->phone,
        ];
    }

    private function presentPatient(?Patient $patient): ?array
    {
        if (!$patient) {
            return null;
        }

        [$parts, $isSplit] = $this->splitAddress($patient->address);

        return [
            'id'                => (int) $patient->id,
            'patient_code'      => $patient->patient_code,
            'customer_id'       => (int) $patient->customer_id,
            'first_name'        => $patient->first_name,
            'last_name'         => $patient->last_name,
            'title'             => $patient->title ?? '',
            'dob'               => $patient->dob?->format('Y-m-d'),
            'gender'            => $patient->gender,
            'email'             => $patient->email,
            'phone'             => $patient->phone,
            'address'           => collect($parts)->filter(fn ($v) => $v !== '')->implode(', '),
            'address_parts'     => $parts,
            'address_is_split'  => $isSplit,
            'status'            => $patient->status,
            'appointment_count' => DB::table('appointments')->where('patient_id', $patient->id)->count(),
        ];
    }

    /**
     * The fuller shape "My Bookings" renders, including the service and category
     * the booking card links to.
     *
     * `title` is the specific test or scan and `service_name` the broader service
     * it sits under; either can be null, so both are sent.
     */
    private function presentAppointmentDetail(Appointment $appointment): array
    {
        $service = null;
        $category = null;

        if ($appointment->service_id) {
            $service = DB::table('services')->where('id', $appointment->service_id)->first();
            if ($service && $service->category_id) {
                $category = DB::table('categories')->where('id', $service->category_id)->first();
            }
        }

        $patient = Patient::find($appointment->patient_id);

        return [
            'id'               => (int) $appointment->id,
            'appointment_code' => $appointment->appointment_code,
            'patient_id'       => (int) $appointment->patient_id,
            'patient_code'     => $patient?->patient_code,
            'patient_name'     => $patient
                ? collect([$patient->title, $patient->first_name, $patient->last_name])
                    ->filter()->implode(' ')
                : null,
            'service_id'       => $appointment->service_id ? (int) $appointment->service_id : null,
            'service_name'     => $service->title ?? $service->service_name ?? null,
            'service_group'    => $service->service_name ?? null,
            'service_price'    => isset($service->price) ? (string) $service->price : null,
            'category_name'    => $category->name ?? null,
            'category_slug'    => $category->slug ?? null,
            'appointment_date' => $appointment->appointment_date?->format('Y-m-d'),
            'start_time'       => $this->formatTime($appointment->start_time),
            'end_time'         => $this->formatTime($appointment->end_time),
            'status'           => $appointment->status,
            'payment_status'   => $appointment->payment_status,
            'source'           => $appointment->source,
            'notes'            => $appointment->notes,
        ];
    }

    private function presentAppointment(?Appointment $appointment): ?array
    {
        if (!$appointment) {
            return null;
        }

        return [
            'id'               => (int) $appointment->id,
            'appointment_code' => $appointment->appointment_code,
            'patient_id'       => (int) $appointment->patient_id,
            'service_id'       => $appointment->service_id ? (int) $appointment->service_id : null,
            'appointment_date' => $appointment->appointment_date?->format('Y-m-d'),
            'start_time'       => $this->formatTime($appointment->start_time),
            'status'           => $appointment->status,
            'payment_status'   => $appointment->payment_status,
            'source'           => $appointment->source,
        ];
    }

    /**
     * `patients` has one `address` text column rather than seven, so the parts
     * are packed reversibly, keeping all seven positions including empty ones so
     * a part cannot drift into the wrong field. A lone filled part is stored bare,
     * which keeps rows written before this encoding looking unsplit.
     */
    private function joinAddress(Request $request): ?string
    {
        $parts = [];
        foreach (self::ADDRESS_KEYS as $key) {
            $parts[] = trim((string) $request->input(self::requestKeyFor($key), ''));
        }

        $filled = array_values(array_filter($parts, fn ($part) => $part !== ''));

        if ($filled === []) {
            return null;
        }

        return count($filled) === 1
            ? $filled[0]
            : implode(self::ADDRESS_SEPARATOR, $parts);
    }

    private function splitAddress(?string $stored): array
    {
        $stored = trim((string) $stored);

        if ($stored === '') {
            return [array_fill_keys(self::ADDRESS_KEYS, ''), false];
        }

        if (!str_contains($stored, self::ADDRESS_SEPARATOR)) {
            $parts = array_fill_keys(self::ADDRESS_KEYS, '');
            $parts['address1'] = $stored;

            return [$parts, false];
        }

        $pieces = explode(self::ADDRESS_SEPARATOR, $stored);
        $parts = array_fill_keys(self::ADDRESS_KEYS, '');

        foreach (self::ADDRESS_KEYS as $index => $key) {
            $parts[$key] = trim($pieces[$index] ?? '');
        }

        return [$parts, true];
    }

    private static function requestKeyFor(string $key): string
    {
        return [
            'address1' => 'address_line_1',
            'address2' => 'address_line_2',
            'zipCode'  => 'zip_code',
        ][$key] ?? $key;
    }

    private function normaliseDate($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        // Rejects impossible dates like 2026-02-31, which PHP would otherwise
        // silently roll over into the next month.
        return checkdate($month, $day, $year) ? $value : null;
    }

    /**
     * Converts a slot time to MySQL TIME ("HH:MM:SS").
     *
     * The wizard offers its slots as "09:00 AM", so 12-hour input is the normal
     * case rather than an edge case; 24-hour is accepted too so a future caller
     * is not forced into a single format.
     */
    private function normaliseTime($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $value, $m)) {
            $hour = (int) $m[1];
            $minute = (int) $m[2];
            $second = (int) ($m[3] ?? 0);

            if ($hour > 23 || $minute > 59 || $second > 59) {
                return null;
            }

            return sprintf('%02d:%02d:%02d', $hour, $minute, $second);
        }

        // Also accepts "10:30AM" and "10:30 A.M." alongside "10:30 AM".
        if (preg_match('/^(\d{1,2}):(\d{2})\s*([AaPp])\.?[Mm]\.?$/', $value, $m)) {
            $hour = (int) $m[1];
            $minute = (int) $m[2];
            $meridiem = strtoupper($m[3]);

            if ($hour < 1 || $hour > 12 || $minute > 59) {
                return null;
            }

            // 12 AM is midnight and 12 PM is noon, so only the rest shift.
            if ($meridiem === 'P' && $hour !== 12) {
                $hour += 12;
            }
            if ($meridiem === 'A' && $hour === 12) {
                $hour = 0;
            }

            return sprintf('%02d:%02d:00', $hour, $minute);
        }

        return null;
    }

    private function formatTime($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof \DateTimeInterface
            ? $value->format('H:i')
            : substr((string) $value, 0, 5);
    }

    private function nullableString($value, ?int $maxLength = null): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return $maxLength === null ? $value : mb_substr($value, 0, $maxLength);
    }
}