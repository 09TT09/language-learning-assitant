<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mistakes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();

            $table->string('type', 50);
            $table->string('subtype', 50)->default('other')->after('type');
            $table->text('original_text');
            $table->text('corrected_text');
            $table->text('explanation');
            $table->string('severity', 20)->default('medium');

            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['type', 'subtype']);
            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mistakes');
    }
};
