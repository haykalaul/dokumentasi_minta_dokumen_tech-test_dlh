<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'old_status' => $this->old_status,
            'new_status' => $this->new_status,
            'notes' => $this->notes,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'user' => [
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
        ];
    }
}
