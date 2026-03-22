<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->change();
            $table->unsignedBigInteger('user_id2')->change();
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->change();
        });

        Schema::table('release_tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('release_id')->change();
        });

        Schema::table('ticket_estimates', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->change();
        });

        Schema::table('ticket_user_watchers', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->change();
        });

        Schema::table('ticket_views', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->change();
        });

        Schema::table('milestone_user_watchers', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->change();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->integer('user_id')->unsigned()->change();
            $table->integer('user_id2')->unsigned()->change();
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->integer('user_id')->unsigned()->change();
        });

        Schema::table('release_tickets', function (Blueprint $table) {
            $table->integer('release_id')->unsigned()->change();
        });

        Schema::table('ticket_estimates', function (Blueprint $table) {
            $table->integer('user_id')->unsigned()->change();
        });

        Schema::table('ticket_user_watchers', function (Blueprint $table) {
            $table->integer('user_id')->unsigned()->change();
        });

        Schema::table('ticket_views', function (Blueprint $table) {
            $table->integer('user_id')->unsigned()->change();
        });

        Schema::table('milestone_user_watchers', function (Blueprint $table) {
            $table->integer('user_id')->unsigned()->change();
        });
    }
};
