<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StoreItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'description',
        'image_url',
        'price',
        'rarity',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }    

    public function owners()
    {
        return $this->belongsToMany(User::class, 'user_inventory')
            ->withPivot(['quantity', 'acquired_at'])
            ->withTimestamps();
    }
}
