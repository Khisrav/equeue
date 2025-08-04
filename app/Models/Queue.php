<?php

namespace App\Models;

use App\Events\QueueUpdated;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\MedicalInstitution;

class Queue extends Model
{
    use HasUuids;

    /**
     * Indicates if the model's ID is auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     */
    protected $keyType = 'string';

    protected $fillable = [
        'patient_name',
        'patient_phone',
        'patient_gender',
        'medical_institution_id',
        'doctor_id',
        'status',
        'notes',
        'start_time',
        'end_time',
        'ticket_number',
    ];

    protected static function booted()
    {
        static::creating(function ($queue) {
            $today = now()->toDateString();

            // Count tickets today for this doctor
            $count = self::where('doctor_id', $queue->doctor_id)
                ->whereDate('created_at', $today)
                ->count();

            // Get doctor's room number using relationship
            $queue->load('doctor');
            $roomNumber = $queue->doctor ? $queue->doctor->room_number : '000';

            $next = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            $randomLetter = chr(rand(65, 90)); // Random uppercase letter A-Z
            $queue->ticket_number = "{$randomLetter}{$roomNumber}-$next";
        });

        // Broadcast queue updates when created, updated, or deleted
        static::created(function ($queue) {
            $medicalInstitutionId = $queue->doctor->medical_institution_id ?? $queue->medical_institution_id;
            if ($medicalInstitutionId) {
                broadcast(new QueueUpdated($medicalInstitutionId));
            }
        });

        static::updated(function ($queue) {
            $medicalInstitutionId = $queue->doctor->medical_institution_id ?? $queue->medical_institution_id;
            if ($medicalInstitutionId) {
                broadcast(new QueueUpdated($medicalInstitutionId));
            }
        });

        static::deleted(function ($queue) {
            $medicalInstitutionId = $queue->doctor->medical_institution_id ?? $queue->medical_institution_id;
            if ($medicalInstitutionId) {
                broadcast(new QueueUpdated($medicalInstitutionId));
            }
        });
    }
    
    public static function getStatuses()
    {
        return [
            'waiting' => 'Ожидание',
            'called' => 'Вызван',
            'skipped' => 'Пропущен',
            'done' => 'Завершен',
            'canceled' => 'Отменен',
        ];
    }

    /**
     * Get the doctor that owns the queue.
     */
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the medical institution that owns the queue.
     */
    public function medicalInstitution()
    {
        return $this->belongsTo(MedicalInstitution::class);
    }
}
