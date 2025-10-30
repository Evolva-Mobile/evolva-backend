<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journey_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['normal', 'especial', 'boss'])->default('normal');
            $table->int('xp_reward')->default(0);
            $table->int('coin_reward')->default(0);
            $table->dateTime('deadline')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->boolean('requires_proof')->default(false);
            $table->string('proof_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('tasks');
    }
};
