<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topic_step_character', function (Blueprint $table) {
            $table->id();

            $table->foreignId('topic_step_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('topic_character_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'topic_step_id',
                'topic_character_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topic_step_character');
    }
};