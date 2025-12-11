use Illuminate\Http\Resources\Json\JsonResource;

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $daysRemaining = $this->deadline ? now()->diffInDays($this->deadline, false) : null;
        $userAssigned = $this->users->contains('id', $request->user()?->id);
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'xp_reward' => $this->xp_reward,
            'coin_reward' => $this->coin_reward,
            'days_remaining' => $daysRemaining,
            'requires_proof' => $this->requires_proof,
            'user_assigned' => $userAssigned ? true : false,
        ];
    }
}