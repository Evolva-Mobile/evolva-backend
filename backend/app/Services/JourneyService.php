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
}
