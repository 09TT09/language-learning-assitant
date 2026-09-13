<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
        });

        $userId = DB::table('users')->orderBy('id')->value('id');

        if ($userId) {
            DB::table('conversations')
                ->whereNull('user_id')
                ->update(['user_id' => $userId]);
        } else {
            DB::table('conversations')->whereNull('user_id')->delete();
        }

        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
