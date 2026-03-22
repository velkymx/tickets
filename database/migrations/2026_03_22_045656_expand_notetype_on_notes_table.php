<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE notes MODIFY COLUMN notetype ENUM('message', 'changelog', 'misc', 'decision', 'blocker', 'update', 'action') NOT NULL DEFAULT 'message'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE notes MODIFY COLUMN notetype ENUM('message', 'changelog', 'misc') NOT NULL DEFAULT 'message'");
    }
};
