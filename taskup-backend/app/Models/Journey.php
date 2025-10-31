<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Journey extends Model {
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

    protected static function booted() {
        static::creating(function ($journey) {
            $journey->join_code = strtoupper(Str::random(6)); //TODOIN: melhorar essa geração de código
        });
    }
    
    public function users() {
        return $this->belongsToMany(User::class, 'journey_user')
                    ->withPivot('is_master')
                    ->withTimestamps();
    }

    public function tasks() {
        return $this->hasMany(Task::class);
    }
}
