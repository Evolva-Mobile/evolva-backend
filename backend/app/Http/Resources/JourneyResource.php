<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JourneyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,
            'join_code'   => $this->join_code,
            'is_private'  => $this->is_private,

            'members' => $this->whenLoaded('users', function () {
                return $this->users->map(function ($user) {
                    return [
                        'id'        => $user->id,
                        'name'      => $user->name,
                        'avatar'    => $user->avatar_url,
                        'is_master' => (bool) $user->pivot->is_master,
                    ];
                });
            }),

            'tasks' => $this->whenLoaded('tasks', function () {
                return $this->tasks->where('type', '!=', 'boss')->map(function ($task) { //TODO: adicionar o ajuste já carregado na service, para evitar consulta adicional na resource
                    $daysRemaining = $task->deadline ? now()->diffInDays($task->deadline, false) : null;
                    $haveAssignedPerson = $task->users->isNotEmpty();
                    return [
                        'id'          => $task->id,
                        'title'       => $task->title,
                        'days_remaining' => $daysRemaining,
                        'have_assigned_person' => $haveAssignedPerson,
                    ];
                });
            }),

            'tasks_boss' => $this->whenLoaded('tasks', function () {
                return $this->tasks->where('type', 'boss')->map(function ($task) { //TODO: adicionar o ajuste já carregado na service, para evitar consulta adicional na resource
                    $daysRemaining = $task->deadline ? now()->diffInDays($task->deadline, false) : null;
                    $haveAssignedPerson = $task->users->isNotEmpty();
                    return [
                        'id'          => $task->id,
                        'title'       => $task->title,
                        'days_remaining' => $daysRemaining,
                        'have_assigned_person' => $haveAssignedPerson,
                    ];
                });
            }),
        ];
    }
}
