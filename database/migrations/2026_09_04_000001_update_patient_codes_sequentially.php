<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Patient;
use App\Models\Customer;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Patient::orderBy('id', 'asc')->get()->each(function ($patient, $index) {
            $patient->update([
                'patient_code' => 'PAT-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT)
            ]);
        });

        Customer::orderBy('id', 'asc')->get()->each(function ($customer, $index) {
            $customer->update([
                'customer_code' => 'CUST-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT)
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No action required on rollback
    }
};
