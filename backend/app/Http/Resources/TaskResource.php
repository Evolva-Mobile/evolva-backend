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
        // deadline válida e futura
        $daysRemaining = null;
        if ($this->deadline && now()->lte($this->deadline)) {
            $daysRemaining = now()->diffInDays($this->deadline, false);
        }

        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'description'   => $this->description,
            'type'          => $this->type,
            'xp_reward'     => $this->xp_reward,
            'coin_reward'   => $this->coin_reward,
            'days_remaining'=> $daysRemaining,
            'requires_proof'=> $this->requires_proof,
            'is_completed'  => $this->is_completed,

            // Se alguém pegou
            'is_taken' => $this->users->isNotEmpty(),

            // Se o usuário logado pegou
            'user_assigned' => $this->users->contains('id', $request->user()?->id),

            // Lista completa dos players atribuídos
            'assigned_users' => $this->users->map(function ($user) {

                return [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'avatar'     => $user->avatar_url,

                    'status'        => $user->pivot->status,
                    'assigned_at'   => $user->pivot->assigned_at,
                    'proof_url'     => $user->pivot->proof_url,
                    'completed_at'  => $user->pivot->completed_at,
                    'xp_earned'     => $user->pivot->xp_earned,
                    'coins_earned'  => $user->pivot->coins_earned,
                ];
            }),

            'assigned_users_count' => $this->users->count(),
        ];
    }
}