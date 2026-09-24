<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topic_step_dependencies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('topic_step_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('depends_on_topic_step_id')
                ->constrained('topic_steps')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'topic_step_id',
                'depends_on_topic_step_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topic_step_dependencies');
    }
};