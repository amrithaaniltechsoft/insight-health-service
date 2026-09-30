<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "My People" — the family and friends a customer books for.
 *
 * This lives here rather than in the Next.js app because it is the only way to
 * reach the database from the deployed frontend: Vercel cannot open a MySQL
 * connection to this server, but Laravel is on this machine and reaches it over
 * localhost. So the Next.js API routes proxy through here.
 *
 * Family members are `patients`, never `customers`, so nothing in this class
 * writes to the `customers` table.
 */
class PeopleController extends Controller
{
    /**
     * `patients` has one `address` text column rather than seven columns, so the
     * parts are packed into it reversibly, separated by this marker. Seven
     * positions are always kept, including empty ones, so a part can never drift
     * into the wrong field.
     */
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

    /**
     * Lists the signed-in customer's people, excluding the row that represents
     * the customer themself (the one carrying their own email), so the list only
     * ever shows family and friends.
     */
    public function index(Request $request): JsonResponse
    {
        $email = (string) $request->query('customer_email', '');
        if ($email === '') {
            return response()->json(['message' => 'A customer email is required.'], 422);
        }

        $customer = Customer::where('email', $email)->first();

        // No customer row means no people — a guest, or someone who has not
        // registered. That is an empty list, not an error.
        if (!$customer) {
            return response()->json(['people' => [], 'customer_found' => false]);
        }

        $people = Patient::where('customer_id', $customer->id)
            // `NOT IN` is not usable here: SQL treats a comparison against NULL as
            // unknown rather than true, so every relative booked without an email
            // of their own would drop out of the list. A relative usually has no
            // email, so that is most of them.
            ->where(function ($query) use ($customer) {
                $query->whereNull('email')
                    ->orWhere('email', '<>', $customer->email);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Patient $patient) => $this->present($patient, $customer))
            ->values();

        return response()->json([
            'people'         => $people,
            'customer_found' => true,
        ]);
    }

    /** Adds a person, or updates one when `patient_id` identifies a row they own. */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'customer_email' => 'required|email|max:255',
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'patient_id'     => 'nullable|integer',
        ]);

        $customer = Customer::where('email', $request->customer_email)->first();
        if (!$customer) {
            return response()->json(['message' => 'We could not find your account.'], 404);
        }

        $patient = null;

        if ($request->filled('patient_id')) {
            // Scoped to this customer, so one account can never edit another's row.
            $patient = Patient::where('id', $request->patient_id)
                ->where('customer_id', $customer->id)
                ->first();

            if (!$patient) {
                return response()->json(['message' => 'We could not find that person on your account.'], 404);
            }
        }

        $existed = $patient !== null;

        $attributes = [
            'customer_id' => $customer->id,
            'first_name'  => $request->input('first_name'),
            'last_name'   => $request->input('last_name'),
            'gender'      => $request->input('gender'),
            'dob'         => $this->normaliseDate($request->input('dob')),
            'email'       => $this->nullableString($request->input('email')),
            'phone'       => $this->nullableString($request->input('phone'), 50),
            'address'     => $this->joinAddress($request),
        ];

        // `title` was added to the schema after this code was written, so it is
        // only written when the column actually exists. Naming a column the
        // database does not have fails the whole statement, which would take the
        // entire add/edit path down over a title.
        if (Schema::hasColumn('patients', 'title')) {
            $attributes['title'] = $this->nullableString($request->input('title'), 255);
        }

        if ($existed) {
            $patient->update($attributes);
        } else {
            $patient = new Patient($attributes);
            $patient->status = 'active';
            $patient->save();
        }

        return response()->json([
            'message' => $existed
                ? 'Person updated successfully.'
                : 'Person added successfully.',
            'created' => !$existed,
            'person'  => $this->present($patient->fresh(), $customer),
        ]);
    }

    /**
     * Removes one of the customer's people.
     *
     * `appointments.patient_id` is ON DELETE CASCADE, so deleting a patient who
     * has ever booked would silently take their appointment history with them.
     * The schema cannot be changed to add a RESTRICT constraint, so the check
     * happens here instead.
     */
    public function destroy(Request $request): JsonResponse
    {
        $email = (string) $request->query('customer_email', '');
        $patientId = $request->query('patient_id');

        if ($email === '' || !is_numeric($patientId)) {
            return response()->json(['message' => 'A valid person is required.'], 422);
        }

        $customer = Customer::where('email', $email)->first();
        if (!$customer) {
            return response()->json(['message' => 'We could not find your account.'], 404);
        }

        $patient = Patient::where('id', (int) $patientId)
            ->where('customer_id', $customer->id)
            ->first();

        if (!$patient) {
            return response()->json(['message' => 'We could not find that person on your account.'], 404);
        }

        $appointmentCount = DB::table('appointments')
            ->where('patient_id', $patient->id)
            ->count();

        if ($appointmentCount > 0) {
            return response()->json([
                'message' => 'This person has bookings, so their record cannot be removed. Please contact the clinic if it needs to be corrected.',
                'appointment_count' => $appointmentCount,
            ], 409);
        }

        $name = trim($patient->first_name . ' ' . $patient->last_name);
        $code = $patient->patient_code;
        $patient->delete();

        return response()->json([
            'message' => 'Person removed successfully.',
            'deleted' => [
                'id'           => (int) $patientId,
                'patient_code' => $code,
                'name'         => $name,
            ],
        ]);
    }

    /** Shapes a patient row for the frontend. */
    private function present(Patient $patient, Customer $customer): array
    {
        [$parts, $isSplit] = $this->splitAddress($patient->address);

        return [
            'id'           => (int) $patient->id,
            'patient_code' => $patient->patient_code,
            'customer_id'  => (int) $patient->customer_id,
            'first_name'   => $patient->first_name,
            'last_name'    => $patient->last_name,
            'title'        => $patient->title ?? '',
            'dob'          => $patient->dob?->format('Y-m-d'),
            'gender'       => $patient->gender,
            'email'        => $patient->email,
            'phone'        => $patient->phone,
            'address'      => $this->formatAddress($parts),
            'address_parts' => $parts,
            'address_is_split' => $isSplit,
            'status'       => $patient->status,
            'appointment_count' => DB::table('appointments')
                ->where('patient_id', $patient->id)
                ->count(),
            'created_at'   => $patient->created_at?->toDateTimeString(),
        ];
    }

    /**
     * Packs the seven submitted address parts into the single `address` column.
     *
     * A lone filled part is stored bare, with no separators. That keeps rows
     * written before this encoding existed looking unsplit, so re-saving one
     * cannot quietly change how it is interpreted.
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

        if (count($filled) === 1) {
            return $filled[0];
        }

        return implode(self::ADDRESS_SEPARATOR, $parts);
    }

    /** Reverses the packing, and reports whether the stored value was packed. */
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

        // Extra pieces cannot happen with seven inputs, but a longer stored value
        // must not shift every later part into the wrong field.
        foreach (self::ADDRESS_KEYS as $index => $key) {
            $parts[$key] = trim($pieces[$index] ?? '');
        }

        return [$parts, true];
    }

    /** One display line, skipping the parts that are empty. */
    private function formatAddress(array $parts): string
    {
        return collect($parts)
            ->filter(fn ($value) => $value !== '')
            ->implode(', ');
    }

    /** Maps the frontend's part names onto the request keys it sends. */
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

        return $value === '' ? null : $value;
    }

    private function nullableString($value, ?int $maxLength = null): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return $maxLength === null
            ? $value
            : mb_substr($value, 0, $maxLength);
    }
}