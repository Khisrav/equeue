<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    protected $fillable = ['id', 'patient_id', 'medical_institution_id', 'doctor_id', 'status', 'notes', 'doctor_notes', 'start_time', 'end_time'];

    protected static function booted()
    {
        static::creating(function ($queue) {
            $today = now()->toDateString();
    
            // Count tickets today for this doctor
            $count = self::where('doctor_id', $queue->doctor_id)
                ->whereDate('created_at', $today)
                ->count();
    
            $next = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            $queue->ticket_number = "D{$queue->room_number}-$next";
        });
    }
}
