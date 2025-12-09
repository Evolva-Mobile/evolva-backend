<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\AuthorizationException;

class TaskService
{
    public function assignTaskToUser(int $taskId, User $user)
    {
        $task = Task::findOrFail($taskId);

        // Verifica se o usuário participa da mesma Journey
        if (!$user->journeys()->where('journey_id', $task->journey_id)->exists()) {
            throw new AuthorizationException('Usuário não pertence a esta jornada.');
        }

        if ($user->tasks()->where('task_id', $taskId)->exists()) {
            throw new AuthorizationException('Tarefa já atribuída ao usuário.');
        }

        $user->tasks()->syncWithoutDetaching([
            $taskId => ['assigned_at' => now()]
        ]);
    }

    public function requestEvaluation(array $data, User $user)
    {
        $taskId = $data['task_id'];
        $proofUrl = $data['proof_url'] ?? null;

        $task = Task::findOrFail($taskId);

        if (!$user->journeys()->where('journey_id', $task->journey_id)->exists()) {
            throw new AuthorizationException('Usuário não pertence a esta jornada.');
        }

        if (!$user->tasks()->where('task_id', $taskId)->exists()) {
            throw new AuthorizationException('Tarefa não pertence ao usuário.');
        }

        if ($task->requires_proof && is_null($proofUrl)) {
            throw new AuthorizationException('Prova é obrigatória para esta tarefa.');
        }

        $user->tasks()->updateExistingPivot($taskId, [
            'status'   => 'pending',
            'proof_url' => $proofUrl,
        ]);
    }
    
    public function evaluateTask(int $userId, string $status, User $master, int $taskId)
    {
        $task = Task::findOrFail($taskId);
        $player = User::findOrFail($userId);

        $isMaster = $master->journeys()
            ->where('journeys.id', $task->journey_id)
            ->wherePivot('is_master', true)
            ->exists();

        if (!$isMaster) {
            throw new AuthorizationException('Apenas o mestre da jornada pode avaliar tarefas.');
        }

        if (!$player->journeys()->where('journey_id', $task->journey_id)->exists()) {
            throw new AuthorizationException('O jogador não pertence a esta jornada.');
        }

        if (!$player->tasks()->where('task_id', $taskId)->exists()) {
            throw new AuthorizationException('Tarefa não pertence ao jogador.');
        }

        DB::transaction(function () use ($player, $task, $taskId, $status) {
            $updateData = [
                'status' => $status,
                'updated_at' => now(),
            ];

            if ($status === 'approved') {
                $updateData['xp_earned'] = $task->xp_reward;
                $updateData['coins_earned'] = $task->coin_reward;
                $updateData['completed_at'] = now();
                $updateData['status'] = 'approved';

                $player->increment('xp', $task->xp_reward);
                $player->increment('coins', $task->coin_reward);

                $task->update(['is_completed' => true]);
            } else {
                $updateData['status'] = 'rejected';
            }

            $player->tasks()->updateExistingPivot($taskId, $updateData);
        });
    }

    public function createTask(array $data, User $user): Task
    {
        return DB::transaction(function () use ($data, $user) {

            // Criar a tarefa diretamente via relacionamento da Journey (mais limpo)
            $journey = $user->journeys()
                ->where('journey_id', $data['journey_id'])
                ->wherePivot('is_master', true)
                ->firstOrFail();

            return $journey->tasks()->create([
                'title'          => $data['title'],
                'description'    => $data['description'] ?? null,
                'type'           => $data['type'] ?? 'normal',
                'xp_reward'      => $data['xp_reward'],
                'coin_reward'    => $data['coin_reward'],
                'deadline'       => $data['deadline'] ?? null,
                'is_completed'   => false,
                'requires_proof' => $data['requires_proof'] ?? false,
                'proof_url'      => $data['proof_url'] ?? null,
                'created_by'     => $user->id
            ]);
        });
    }

    public function getTaskById (int $taskId): Task
    {
        return Task::with('users')->findOrFail($id);
    }
}
