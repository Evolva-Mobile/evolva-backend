<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Journey extends Model
{
    /**
     * Criar dados falsos para testes, pode remover depois
     * exemplo: Journey::factory()->count(10)->create();
     */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'is_private',
    ];
    
    public function users() {
        return $this->belongsToMany(User::class, 'journey_user')
                    ->withPivot('is_master')
                    ->withTimestamps();
    }

    public function tasks() {
        return $this->hasMany(Task::class);
    }
}
