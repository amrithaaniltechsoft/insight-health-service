<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Patient;
use App\Models\Payment;
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

    private const GENDERS = ['Male', 'Female', 'Non-binary', 'Other', 'Prefer not to say'];

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

        // The wizard saves the person at step 2 but only books the slot once the
        // customer has actually paid, so this flag holds the appointment back
        // until /bookings/confirm is called from the payment step.
        $deferAppointment = $request->boolean('defer_appointment');

        try {
            return DB::transaction(function () use (
                $request, $bookingFor, $firstName, $lastName, $gender, $dob,
                $appointmentDate, $startTime, $email, $serviceName, $deferAppointment
            ) {
                // A single booking can cover several services, so the ids arrive
                // as an array and the first one doubles as the legacy single-id
                // value the rest of the code and admin still read.
                $serviceIds = $this->resolveRequestedServiceIds($request, $serviceName);
                $serviceId = $serviceIds[0] ?? null;

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
                    return $this->bookForOther($request, $attributes, $serviceId, $serviceIds, $appointmentDate, $startTime, $deferAppointment);
                }

                return $this->bookForSelf($request, $attributes, $email, $serviceId, $serviceIds, $appointmentDate, $startTime, $deferAppointment);
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
        array $serviceIds,
        string $appointmentDate,
        string $startTime,
        bool $deferAppointment = false
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

        [$appointment, $appointmentExisted] = $deferAppointment
            ? [null, false]
            : $this->recordAppointment(
                $patientId, $serviceId, $serviceIds, $appointmentDate, $startTime, $request->input('notes')
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
            'appointment_deferred' => $deferAppointment,
            'appointment_created' => !$deferAppointment && !$appointmentExisted,
            'appointment' => $appointment,
        ]);
    }

    /** Booking for the signed-in customer themselves. */
    private function bookForSelf(
        Request $request,
        array $patientAttributes,
        string $email,
        ?int $serviceId,
        array $serviceIds,
        string $appointmentDate,
        string $startTime,
        bool $deferAppointment = false
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

        [$appointment, $appointmentExisted] = $deferAppointment
            ? [null, false]
            : $this->recordAppointment(
                $patientId, $serviceId, $serviceIds, $appointmentDate, $startTime, $request->input('notes')
            );

        return response()->json([
            'message' => $patientExisted
                ? 'Customer and patient updated successfully.'
                : 'Customer and patient created successfully.',
            'booking_for' => 'self',
            'created' => !$customerAlreadyExisted,
            'customer_touched' => true,
            'patient_created' => !$patientExisted,
            'appointment_deferred' => $deferAppointment,
            'appointment_created' => !$deferAppointment && !$appointmentExisted,
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
        array $serviceIds,
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
            'service_ids'      => $serviceIds !== [] ? $serviceIds : ($serviceId !== null ? [$serviceId] : null),
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
     * `title` is the specific test or scan and `service_name` the group it sits
     * under, so the title is tried first and on its own. They cannot be matched
     * together: a group name is shared by every test inside it, so
     * "Occupational Health Screening" is the service_name of three rows, and
     * matching both columns at once made that name permanently ambiguous and
     * left service_id null. MySQL also compares case-insensitively, so a title
     * that happens to equal its own group name still resolves to the single
     * row where both agree.
     *
     * Anything still ambiguous is left null rather than guessed — the column is
     * nullable, and clinic_id and staff_id are assigned by staff later.
     */
    private function resolveServiceId(string $serviceName): ?int
    {
        if ($serviceName === '') {
            return null;
        }

        $byTitle = DB::table('services')
            ->where('title', $serviceName)
            ->limit(2)
            ->get();

        if ($byTitle->count() === 1) {
            return (int) $byTitle->first()->id;
        }

        $byGroup = DB::table('services')
            ->where('service_name', $serviceName)
            ->limit(2)
            ->get();

        return $byGroup->count() === 1 ? (int) $byGroup->first()->id : null;
    }

    /**
     * The service the wizard picked, preferring the id it looked up over the
     * name it displays.
     *
     * Matching on a name alone is best-effort: titles repeat, many rows only
     * carry `service_name`, and the wizard's placeholder ("General
     * Consultation") matches nothing at all — which is how an appointment ends
     * up with no service and nothing ticked in the admin drawer. An explicit id
     * removes the guessing; the name lookup stays for older callers.
     */
    private function resolveRequestedServiceId(Request $request, string $serviceName): ?int
    {
        $requested = $request->input('service_id');

        if (is_numeric($requested) && DB::table('services')->where('id', (int) $requested)->exists()) {
            return (int) $requested;
        }

        return $this->resolveServiceId($serviceName);
    }

    /**
     * The services a booking covers, in the order the wizard picked them.
     *
     * A multi-service booking sends `service_ids` as an array, so every id is
     * checked against `services` and duplicates are dropped before the list is
     * trusted. When the array is missing or empty the single-id path — explicit
     * `service_id`, then the best-effort name lookup — is used, so older callers
     * keep working unchanged and the wizard's fallback to "General
     * Consultation" still resolves (or stays null) as before.
     */
    private function resolveRequestedServiceIds(Request $request, string $serviceName): array
    {
        $requested = $request->input('service_ids');

        if (is_array($requested) && $requested !== []) {
            $ids = [];
            foreach ($requested as $value) {
                if (!is_numeric($value)) {
                    continue;
                }
                $id = (int) $value;
                if ($id > 0 && !in_array($id, $ids, true) && DB::table('services')->where('id', $id)->exists()) {
                    $ids[] = $id;
                }
            }
            if ($ids !== []) {
                return $ids;
            }
        }

        $single = $this->resolveRequestedServiceId($request, $serviceName);

        return $single !== null ? [$single] : [];
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

        // A booking can cover several services, so the whole list is read and
        // the first row doubles as the primary service the bookmark link and
        // category are taken from.
        $storedServiceIds = $appointment->service_ids;
        if (is_array($storedServiceIds) && count($storedServiceIds) > 0) {
            $serviceRows = DB::table('services')->whereIn('id', $storedServiceIds)->get();
        } elseif ($appointment->service_id) {
            $row = DB::table('services')->where('id', $appointment->service_id)->first();
            $serviceRows = $row ? collect([$row]) : collect();
        } else {
            $serviceRows = collect();
        }

        $service = $serviceRows->first();
        if ($service && $service->category_id) {
            $category = DB::table('categories')->where('id', $service->category_id)->first();
        }

        $serviceNames = $serviceRows
            ->map(fn ($row) => $row->title ?? $row->service_name)
            ->filter()
            ->values();

        // Multi-service bookings carry several prices, so the card shows the sum
        // while `service_price` keeps the primary row's price for single-service
        // callers. Prices are decimals in the `services` table; anything null or
        // non-numeric (e.g. POA) is skipped from the total.
        $totalPrice = $serviceRows
            ->pluck('price')
            ->filter(fn ($price) => $price !== null && is_numeric($price))
            ->map(fn ($price) => (float) $price)
            ->sum();

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
            'service_ids'      => is_array($storedServiceIds)
                ? array_map('intval', $storedServiceIds)
                : ($appointment->service_id ? [(int) $appointment->service_id] : []),
            // `service_name` stays the primary one so the "book again" slug
            // keeps resolving; `service_names` carries every service for display.
            'service_name'     => $service->title ?? $service->service_name ?? null,
            'service_names'    => $serviceNames->implode(' + '),
            'service_group'    => $service->service_name ?? null,
            'service_price'    => isset($service->price) ? (string) $service->price : null,
            'total_price'      => $totalPrice > 0 ? (string) round($totalPrice, 2) : null,
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
            'service_ids'      => is_array($appointment->service_ids)
                ? array_map('intval', $appointment->service_ids)
                : ($appointment->service_id ? [(int) $appointment->service_id] : []),
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

    /**
     * Confirms a deferred booking once the user commits on step 4.
     *
     * The person the appointment is for was already written as a `patients` row
     * by store(), so this only adds the appointment and the payment. The patient
     * is identified by the `patient_id` store() handed back, falling back to a
     * name match for older callers.
     *
     * The account that owns that patient is `booked_by_email` — the person
     * paying. It is not the patient's own email: a relative or friend is only a
     * patient and usually has no customer account at all, so looking the
     * customer up on the patient's email is what made booking for someone else
     * fail here.
     */
    public function confirm(Request $request): JsonResponse
    {
        $patientEmail = strtolower(trim((string) $request->input('email')));
        $bookedByEmail = strtolower(trim((string) $request->input('booked_by_email')));

        // The payer owns the booking. Fall back to the patient's own email for a
        // self-booking made without an account.
        $ownerEmail = $bookedByEmail !== '' && filter_var($bookedByEmail, FILTER_VALIDATE_EMAIL)
            ? $bookedByEmail
            : $patientEmail;

        $appointmentDate = $this->normaliseDate($request->input('appointment_date'));
        if ($appointmentDate === null) {
            return response()->json(['message' => 'A valid appointment date is required.'], 422);
        }

        $startTime = $this->normaliseTime($request->input('start_time'));
        if ($startTime === null) {
            return response()->json(['message' => 'A valid appointment time is required.'], 422);
        }

        $notes = $this->nullableString($request->input('notes'), 2000);
        $serviceName = trim((string) $request->input('service_name'));
        $paymentMethod = $this->nullableString($request->input('payment_method'), 255) ?: 'Card';

        // "Continue to Payment" only holds the slot, so it inserts the
        // appointment and leaves the payment to the "Pay" step. The payment row
        // is not written until that button is pressed.
        $recordPayment = $request->boolean('record_payment', true);

        return DB::transaction(function () use (
            $request,
            $ownerEmail,
            $appointmentDate,
            $startTime,
            $notes,
            $serviceName,
            $paymentMethod,
            $recordPayment
        ) {
            $customer = $ownerEmail !== '' ? Customer::where('email', $ownerEmail)->first() : null;

            // store() hands back the patient it wrote. Using it directly is
            // reliable: a relative's name is not unique, and a guest booking for
            // someone else has no customer account to look them up through.
            $patient = null;
            $patientId = $request->input('patient_id');

            if (is_numeric($patientId)) {
                $patient = Patient::find((int) $patientId);

                // Scoped to the payer so one account can never confirm a booking
                // against another account's person.
                if ($patient && $customer && $patient->customer_id !== $customer->id) {
                    $patient = null;
                }
            }

            if (!$patient && $customer) {
                $patient = Patient::where('customer_id', $customer->id)
                    ->where('first_name', trim((string) $request->input('first_name')))
                    ->where('last_name', trim((string) $request->input('last_name')))
                    ->orderBy('id')
                    ->first();
            }

            if (!$patient) {
                return response()->json([
                    'message' => 'We could not find the patient for this booking. Please go back and enter your details again.',
                ], 422);
            }

            // A single booking can cover several services, so the ids arrive as
            // an array; the first one doubles as the legacy single-id value.
            $serviceIds = $this->resolveRequestedServiceIds($request, $serviceName);
            $serviceId = $serviceIds[0] ?? null;

            // Taking the payment for a slot that "Continue to Payment" already
            // inserted: that row is settled rather than duplicated, so the
            // patient does not end up with two appointments for one booking.
            $heldAppointment = null;
            $heldId = $request->input('appointment_id');
            if ($recordPayment && is_numeric($heldId)) {
                $heldAppointment = Appointment::where('id', (int) $heldId)
                    ->where('patient_id', $patient->id)
                    ->first();
            }

            if ($heldAppointment) {
                // A booking held before the service could be resolved never got
                // one; record it now rather than leaving the admin drawer with
                // nothing ticked forever.
                if (!$heldAppointment->service_id && $serviceId !== null) {
                    $heldAppointment->update([
                        'service_id'  => $serviceId,
                        'service_ids' => $serviceIds !== [] ? $serviceIds : [$serviceId],
                    ]);
                }

                $payment = $this->recordPayment(
                    $heldAppointment,
                    $patient,
                    $request->input('amount'),
                    $paymentMethod,
                    $request->input('transaction_ref')
                );

                return response()->json([
                    'message' => 'Booking confirmed successfully.',
                    'appointment' => $this->presentAppointment($heldAppointment->fresh()),
                    'appointment_created' => false,
                    'payment_recorded' => true,
                    'payment' => $payment,
                ]);
            }

            // Always insert a new appointment record as requested - do not update existing rows.
            // It starts as Pending; the Pay step is what marks it Paid.
            $appointment = new Appointment([
                'patient_id'       => $patient->id,
                'service_id'       => $serviceId,
                'service_ids'      => $serviceIds !== [] ? $serviceIds : ($serviceId !== null ? [$serviceId] : null),
                'appointment_date' => $appointmentDate,
                'start_time'       => $startTime,
                'notes'            => $notes,
                'status'           => 'Scheduled',
                'payment_status'   => $recordPayment ? 'Paid' : 'Pending',
                'source'           => 'web_frontend',
            ]);
            $appointment->save();

            // Holding the slot does not take a payment, so there is nothing to
            // write to `payments` yet.
            if (!$recordPayment) {
                return response()->json([
                    'message' => 'Appointment held successfully.',
                    'appointment' => $this->presentAppointment($appointment),
                    'appointment_created' => true,
                    'payment_recorded' => false,
                    'payment' => null,
                ]);
            }

            $payment = $this->recordPayment($appointment, $patient, $request->input('amount'), $paymentMethod, $request->input('transaction_ref'));

            return response()->json([
                'message' => 'Booking confirmed successfully.',
                'appointment' => $this->presentAppointment($appointment),
                'appointment_created' => true,
                'payment_recorded' => true,
                'payment' => $payment,
            ]);
        });
    }

    /**
     * Writes the payment row for a confirmed appointment and marks that
     * appointment paid.
     *
     * Split out of confirm() so the wizard can hold the slot first and take the
     * payment on the following step. The invoice number is only unique by
     * convention here, so it is checked against existing rows rather than
     * trusted, and falls back to a time-based value if a collision somehow
     * survives the retries.
     */
    private function recordPayment(
        Appointment $appointment,
        Patient $patient,
        $amount,
        string $paymentMethod,
        $transactionRef
    ): Payment {
        $invoiceNumber = null;
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $candidate = 'INV-' . strtoupper(bin2hex(random_bytes(4)));
            if (!Payment::where('invoice_number', $candidate)->exists()) {
                $invoiceNumber = $candidate;
                break;
            }
        }
        if ($invoiceNumber === null) {
            $invoiceNumber = 'INV-' . strtoupper(uniqid('', true));
        }

        $amount = $this->nullableString($amount, 255);
        $numericAmount = $amount !== null && is_numeric(str_replace([',', '£', '$'], '', $amount))
            ? (float) preg_replace('/[^0-9.]/', '', $amount)
            : 0.00;

        $payment = new Payment([
            'invoice_number' => $invoiceNumber,
            'appointment_id' => $appointment->id,
            'patient_id'     => $patient->id,
            'subtotal'       => $numericAmount,
            'tax'            => 0.00,
            'discount'       => 0.00,
            'total_amount'   => $numericAmount,
            'amount_paid'    => $numericAmount,
            'payment_method' => $paymentMethod,
            'status'         => 'Paid',
            'transaction_ref'=> $this->nullableString($transactionRef, 255),
        ]);
        $payment->save();

        // A payment row exists, so the appointment is no longer outstanding.
        // Skipped when the appointment was already inserted as Paid.
        if ($appointment->payment_status !== 'Paid') {
            $appointment->update(['payment_status' => 'Paid']);
        }

        return $payment;
    }
}