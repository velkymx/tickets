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
        Schema::table('ticket_views', function (Blueprint $table) {
            $table->unique(['ticket_id', 'user_id'], 'ticket_views_ticket_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_views', function (Blueprint $table) {
            $table->dropUnique('ticket_views_ticket_user_unique');
        });
    }
};
