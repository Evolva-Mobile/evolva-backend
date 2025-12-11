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

            'tasks'      => $this->formatTasksByType('normal'),
            'tasks_boss' => $this->formatTasksByType('boss'),
        ];
    }

    private function formatTasksByType(string $type)
    {
        return $this->whenLoaded('tasks', function () use ($type) {

            return $this->tasks
                ->filter(function ($task) use ($type) {
                    return $type === 'boss'
                        ? $task->type === 'boss'
                        : $task->type !== 'boss';
                })
                ->map(function ($task) {

                    $daysRemaining = null;

                    if ($task->deadline && now()->lte($task->deadline)) {
                        $daysRemaining = now()->diffInDays($task->deadline, false);
                    }

                    return [
                        'id'                    => $task->id,
                        'title'                 => $task->title,
                        'days_remaining'        => $daysRemaining,
                        'have_assigned_person'  => $task->users->isNotEmpty(),
                    ];
                });
        });
    }
}
