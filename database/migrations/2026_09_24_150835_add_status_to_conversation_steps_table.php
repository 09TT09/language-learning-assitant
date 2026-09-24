<?php

use App\Enums\ConversationStepStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversation_steps', function (Blueprint $table) {
            $table->string('status')
                ->default(ConversationStepStatus::LOCKED->value)
                ->after('topic_step_id');

            $table->timestamp('completed_at')
                ->nullable()
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('conversation_steps', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'completed_at',
            ]);
        });
    }
};