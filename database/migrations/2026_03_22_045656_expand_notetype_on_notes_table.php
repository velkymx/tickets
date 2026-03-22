<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite doesn't support ALTER COLUMN with ENUM, so we skip
        // This migration only applies to MySQL/PostgreSQL
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('notes', function (Blueprint $table) {
            $table->string('notetype', 20)->default('message')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('notes', function (Blueprint $table) {
            $table->string('notetype', 20)->default('message')->change();
        });
    }
};
