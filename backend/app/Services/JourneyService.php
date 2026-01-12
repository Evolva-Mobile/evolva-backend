<?php

namespace App\Services;

use App\Models\Journey;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class JourneyService
{
    public function getAllJourneys(User $user): Collection
    {
        return $user->journeys()->with([
            'users:id,name,avatar_url',
            'tasks' => function ($query) {
                $query->where('deadline', '>=', now())
                    ->with('users:id');
            }
        ])->get();
    }

    public function getJourneyById(int $id): Journey
    {
        return Journey::with([
            'users' => function ($query) {
                $query->select('users.id', 'users.name', 'users.avatar_url')
                    ->withPivot('is_master');
            },
            'tasks' => function ($query) {
                $query->where('deadline', '>=', now())
                    ->with('users:id');
            }
        ])
            ->findOrFail($id);
    }

    public function createJourney(array $data, User $user): Journey
    {
        $journey = Journey::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'is_private' => $data['is_private'] ?? true,
            'image_url' => $data['image_url'] ?? null,
        ]);

        // Adiciona o criador como mestre
        $journey->users()->attach($user->id, ['is_master' => true]);

        return $journey;
    }

    public function getPublicJourneys(): Collection
    {
        return Journey::where('is_private', false)
            ->with(['users:id,name,avatar_url', 'tasks' => function ($query) {
                $query->where('deadline', '>=', now())
                    ->with('users:id');
            }])
            ->get();
    }


    public function joinJourney(string $joinCode, User $user): Journey
    {
        $journey = Journey::where('join_code', strtoupper($joinCode))->first();

        if (!$journey) {
            abort(404, 'Jornada não encontrada.');
        }

        // Se já participa, retorna erro 409
        if ($journey->users()->where('user_id', $user->id)->exists()) {
            abort(409, 'Você já está nessa jornada.');
        }

        $journey->users()->attach($user->id, ['is_master' => false]);

        return $journey;
    }

    public function getUsersJourneys(int $journeyId): Collection
    {
        $journey = Journey::with('users')->find($journeyId);

        if (!$journey) {
            abort(404, 'Jornada não encontrada.');
        }

        return $journey->users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_master' => (bool) $user->pivot->is_master,
            ];
        });
    }


    /**
     * Atualiza uma jornada se o usuário for mestre.
     *
     * @param int $id  ID da jornada
     * @param array $data  Dados para atualização validados no request
     * @param User $user  Usuário autenticado que fez a requisição
     * @return Journey
     *
     */
    public function updateJourney(int $id, array $data, User $user): Journey
    {

        $journey = Journey::with('users', 'tasks')->find($id);

        if (!$journey) {
            abort(404, 'Jornada não encontrada.'); //TODO: alterar para throw, adicionei dessa forma para ser mais rápido
        }

        $pivot = $journey->users->firstWhere('id', $user->id);

        if (!$pivot) {
            abort(403, 'Você não participa dessa jornada.');
        }

        if (!$pivot->pivot->is_master) {
            abort(403, 'Apenas mestres podem atualizar a jornada.');
        }

        $fields = [];
        if (!empty($data['title'])) {
            $fields['title'] = $data['title'];
        }

        if (!empty($data['description'])) {
            $fields['description'] = $data['description'];
        }

        if (isset($data['is_private'])) {
            $fields['is_private'] = $data['is_private'];
        }


        if (!empty($fields)) {
            $journey->update($fields);
        }

        return $journey->fresh(['users', 'tasks']);
    }

    public function deleteJourney(int $journeyId, User $user): bool
    {
        $journey = Journey::with('users', 'tasks')->find($journeyId);

        if (!$journey) {
            abort(404, 'Jornada não encontrada.');
        }

        $pivot = $journey->users->firstWhere('id', $user->id);

        if (!$pivot) {
            abort(403, 'Você não participa dessa jornada.');
        }

        if (!$pivot->pivot->is_master) {
            abort(403, 'Apenas mestres podem excluir a jornada.');
        }

        DB::transaction(function () use ($journey) {

            //remover relações pivot journey_user
            $journey->users()->detach();

            //remover todas as tasks e seus pivots (task_user)
            foreach ($journey->tasks as $task) {
                $task->users()->detach(); // pivot task_user
                $task->delete();
            }

            //remover store se existir
            if ($journey->store) {
                $journey->store->delete();
            }

            //deletar jornada
            $journey->delete();

            //futuramente enviar notificação
            //event(new JourneyDeleted($journey));
        });

        return true;
    }
    public function getJourneyRanking(int $journeyId, User $user)
    {
        // Verifica se o usuário pertence à jornada
        $isMember = DB::table('journey_user')
            ->where('journey_id', $journeyId)
            ->where('user_id', $user->id)
            ->exists();

        if (!$isMember) {
            throw new AuthorizationException('Você não faz parte desta jornada.');
        }

        $ranking = DB::table('users')
            ->join('journey_user', 'journey_user.user_id', '=', 'users.id')
            ->leftJoin('task_user', 'task_user.user_id', '=', 'users.id')
            ->leftJoin('tasks', 'tasks.id', '=', 'task_user.task_id')
            ->where('journey_user.journey_id', $journeyId)
            ->where(function ($query) use ($journeyId) {
                $query->whereNull('tasks.journey_id')
                    ->orWhere('tasks.journey_id', $journeyId);
            })
            ->select(
                'users.id as user_id',
                'users.name',
                'users.avatar_url',
                'users.level',
                DB::raw('COALESCE(SUM(task_user.xp_earned), 0) as xp'),
                DB::raw('COUNT(CASE WHEN task_user.status = "approved" THEN 1 END) as missions_completed'),
                DB::raw('MAX(task_user.completed_at) as last_completed_at')
            )
            ->groupBy('users.id', 'users.name', 'users.avatar_url', 'users.level')
            ->orderByDesc('xp')
            ->orderByDesc('users.level')
            ->orderByDesc('last_completed_at')
            ->get();

        // Adiciona posição no ranking
        return $ranking->values()->map(function ($item, $index) {
            $item->rank = $index + 1;
            return $item;
        });
    }
}
