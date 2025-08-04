<?php

namespace App\Events;

use App\Models\MedicalInstitution;
use App\Models\Queue;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $medicalInstitutionId;
    public $queueData;

    /**
     * Create a new event instance.
     */
    public function __construct($medicalInstitutionId)
    {
        $this->medicalInstitutionId = $medicalInstitutionId;
        $this->queueData = $this->getQueueData();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('queue-monitor.' . $this->medicalInstitutionId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'queue.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'data' => $this->queueData
        ];
    }

    /**
     * Get queue data for the medical institution
     */
    private function getQueueData()
    {
        $medicalInstitution = MedicalInstitution::find($this->medicalInstitutionId);
        
        // Get today's queues for the medical institution
        $queues = Queue::with(['doctor.medicalInstitution'])
            ->whereHas('doctor', function ($query) {
                $query->where('medical_institution_id', $this->medicalInstitutionId);
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
        $doctors = User::where('medical_institution_id', $this->medicalInstitutionId)
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

        return [
            'queues' => $queues,
            'doctors' => $doctors,
            'institution_name' => $medicalInstitution->name ?? 'Медицинское учреждение',
            'updated_at' => now()->toISOString(),
        ];
    }
}
