<?php
namespace App\Services;

use App\Models\Store;
use App\Models\StoreItem;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class StoreService
{
    public function getOrCreateStore(int $journeyId): Store
    {
        return Store::firstOrCreate(
            ['journey_id' => $journeyId],
            [
                'name' => 'Loja da Jornada',
                'description' => 'Itens personalizados da jornada'
            ]
        );
    }

    public function getItems(int $journeyId)
    {
        $store = $this->getOrCreateStore($journeyId);

        return $store->items()->orderBy('price')->get();
    }

    public function createItem(int $journeyId, array $data, User $user): StoreItem
    {
        $store = $this->getOrCreateStore($journeyId);

        $isMaster = $user->journeys()
            ->where('journey_id', $journeyId)
            ->wherePivot('is_master', true)
            ->exists();

        if (!$isMaster) {
            throw new AuthorizationException('Apenas o mestre pode cadastrar itens.');
        }

        return $store->items()->create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'image_url'   => $data['image_url'] ?? null,
            'price'       => $data['price'],
            'rarity'      => $data['rarity'] ?? 'common',
        ]);
    }
}
