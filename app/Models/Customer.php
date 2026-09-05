<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'customer_code',
        'email',
        'first_name',
        'last_name',
        'gender',
        'title',
        'dob',
        'phone',
        'address_line_1',
        'address_line_2',
        'suburb',
        'city',
        'state',
        'zip_code',
        'country',
        'medical_history',
        'status',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
        ];
    }

    protected static function booted()
    {
        static::creating(function ($customer) {
            if (empty($customer->customer_code)) {
                $customer->customer_code = static::generateNextCustomerCode();
            }
        });
    }

    public static function generateNextCustomerCode(): string
    {
        $lastCustomer = static::orderBy('id', 'desc')->first();
        $nextId = $lastCustomer ? ($lastCustomer->id + 1) : 1;

        $maxCodeNum = static::where('customer_code', 'like', 'CUST-%')
            ->get()
            ->map(function ($c) {
                return intval(preg_replace('/[^0-9]/', '', $c->customer_code));
            })
            ->max() ?? 0;

        if ($maxCodeNum >= $nextId) {
            $nextId = $maxCodeNum + 1;
        }

        return 'CUST-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    public function patients()
    {
        return $this->hasMany(Patient::class, 'customer_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    public function clinicalNotes()
    {
        return $this->hasMany(ClinicalNote::class, 'patient_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'patient_id');
    }
}
