<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Allow unassigned tickets (no assignee) created via the API/MCP.
            $table->integer('user_id2')->unsigned()->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->integer('user_id2')->unsigned()->nullable(false)->change();
        });
    }
};
