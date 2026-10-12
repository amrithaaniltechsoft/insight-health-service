<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicSetting extends Model
{
    use HasFactory;

    protected $table = 'clinic_settings';

    protected $fillable = [
        'opening_time',
        'closing_time',
        'public_holidays',
        'same_day_booking',
        'cancellation_deadline_hours',
        'deposit_amounts',
        'payment_rules',
        'refund_policies',
    ];

    protected $casts = [
        'cancellation_deadline_hours' => 'integer',
    ];
}
