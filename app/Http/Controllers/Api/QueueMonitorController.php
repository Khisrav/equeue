<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Queue;
use App\Models\User;
use App\Models\MedicalInstitution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QueueMonitorController extends Controller
{
    public function index(Request $request)
    {
        // Get the current user's medical institution or use a specific one
        $medicalInstitutionId = $request->query('institution_id');
        
        if (!$medicalInstitutionId && Auth::check()) {
            $medicalInstitutionId = Auth::user()->medical_institution_id;
        }
        
        if (!$medicalInstitutionId) {
            // If no institution specified, get the first one
            $medicalInstitution = MedicalInstitution::first();
            $medicalInstitutionId = $medicalInstitution?->id;
        } else {
            $medicalInstitution = MedicalInstitution::find($medicalInstitutionId);
        }

        // Get today's queues for the medical institution
        $queues = Queue::with(['doctor.medicalInstitution'])
            ->whereHas('doctor', function ($query) use ($medicalInstitutionId) {
                $query->where('medical_institution_id', $medicalInstitutionId);
            })
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($queue) {
                return [
                    'id' => $queue->id,
                    'ticket_number' => $queue->ticket_number,
                    'patient_name' => $queue->patient_name,
                    'status' => $queue->status,
                    'doctor' => [
                        'id' => $queue->doctor->id,
                        'name' => $queue->doctor->name,
                        'specialization' => $queue->doctor->specialization ?? 'Не указано',
                        'room_number' => $queue->doctor->room_number ?? 'Н/Д',
                    ],
                    'created_at' => $queue->created_at->toISOString(),
                ];
            });

        // Get doctors for the medical institution
        $doctors = User::where('medical_institution_id', $medicalInstitutionId)
            ->whereHas('roles')
            ->get()
            ->map(function ($doctor) {
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->name,
                    'specialization' => $doctor->specialization ?? 'Не указано',
                    'room_number' => $doctor->room_number ?? 'Н/Д',
                ];
            });

        return response()->json([
            'queues' => $queues,
            'doctors' => $doctors,
            'institution_name' => $medicalInstitution->name ?? 'Медицинское учреждение',
            'updated_at' => now()->toISOString(),
        ]);
    }
} 