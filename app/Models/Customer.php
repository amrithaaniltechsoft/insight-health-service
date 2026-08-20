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
