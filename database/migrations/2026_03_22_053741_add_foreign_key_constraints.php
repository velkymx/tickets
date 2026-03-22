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
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('type_id')->references('id')->on('types')->onDelete('restrict');
            $table->foreign('status_id')->references('id')->on('statuses')->onDelete('restrict');
            $table->foreign('importance_id')->references('id')->on('importances')->onDelete('restrict');
            $table->foreign('milestone_id')->references('id')->on('milestones')->onDelete('restrict');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('restrict');
            $table->foreign('user_id2')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
        });

        Schema::table('ticket_estimates', function (Blueprint $table) {
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('ticket_user_watchers', function (Blueprint $table) {
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('release_tickets', function (Blueprint $table) {
            $table->foreign('release_id')->references('id')->on('releases')->onDelete('cascade');
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['type_id']);
            $table->dropForeign(['status_id']);
            $table->dropForeign(['importance_id']);
            $table->dropForeign(['milestone_id']);
            $table->dropForeign(['project_id']);
            $table->dropForeign(['user_id2']);
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['ticket_id']);
        });

        Schema::table('ticket_estimates', function (Blueprint $table) {
            $table->dropForeign(['ticket_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('ticket_user_watchers', function (Blueprint $table) {
            $table->dropForeign(['ticket_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('release_tickets', function (Blueprint $table) {
            $table->dropForeign(['release_id']);
            $table->dropForeign(['ticket_id']);
        });
    }
};
