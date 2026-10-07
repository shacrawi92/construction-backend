<?php

namespace App\Notifications;

use App\Models\MaterialRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class MaterialRequestReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly MaterialRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload());
    }

    public function broadcastOn(): array
    {
        return [new \Illuminate\Broadcasting\PrivateChannel("foreman.{$this->request->foreman_id}")];
    }

    public function broadcastAs(): string
    {
        return 'material-request.reviewed';
    }

    private function payload(): array
    {
        return [
            'type'                => 'material_request_reviewed',
            'material_request_id' => $this->request->id,
            'project_name'        => $this->request->project->name,
            'status'              => $this->request->status,
            'rejection_note'      => $this->request->rejection_note,
            'reviewed_at'         => $this->request->reviewed_at?->toISOString(),
        ];
    }
}
