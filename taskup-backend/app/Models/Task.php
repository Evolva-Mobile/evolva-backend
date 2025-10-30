<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model {
    
    /**
     * Criar dados falsos para testes, pode remover depois
     * exemplo: Task::factory()->count(10)->create();
     */
    use HasFactory;

    protected $fillable = [
        'journey_id',
        'title',
        'description',
        'type',
        'xp_reward',
        'coin_reward',
        'deadline',
        'is_completed',
        'requires_proof',
        'proof_url',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'is_completed' => 'boolean',
        'requires_proof' => 'boolean',
    ];

    public function journey() {
        return $this->belongsTo(Journey::class);
    }

    public function users() {
        return $this->belongsToMany(User::class, 'task_user')
                    ->withPivot('') //TODOIN: ajustar esses pivots
                    ->withTimestamps();
    }
}
