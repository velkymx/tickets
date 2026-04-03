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
        Schema::table('tickets', function (Blueprint $table) {
            $table->index(['status_id', 'project_id']);
            $table->index(['user_id2', 'status_id']);
            $table->index(['milestone_id', 'status_id']);
            $table->index('type_id');
            $table->index('importance_id');
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->index(['ticket_id', 'hide']);
            $table->index(['user_id', 'created_at']);
            $table->index('notetype');
        });

        Schema::table('ticket_user_watchers', function (Blueprint $table) {
            $table->index(['ticket_id', 'user_id']);
        });

        Schema::table('milestones', function (Blueprint $table) {
            $table->index('owner_user_id');
            $table->index('scrummaster_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['status_id', 'project_id']);
            $table->dropIndex(['user_id2', 'status_id']);
            $table->dropIndex(['milestone_id', 'status_id']);
            $table->dropIndex(['type_id']);
            $table->dropIndex(['importance_id']);
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->dropIndex(['ticket_id', 'hide']);
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['notetype']);
        });

        Schema::table('ticket_user_watchers', function (Blueprint $table) {
            $table->dropIndex(['ticket_id', 'user_id']);
        });

        Schema::table('milestones', function (Blueprint $table) {
            $table->dropIndex(['owner_user_id']);
            $table->dropIndex(['scrummaster_user_id']);
        });
    }
};
