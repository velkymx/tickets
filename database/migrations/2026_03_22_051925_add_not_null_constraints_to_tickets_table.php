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
            $table->unsignedBigInteger('type_id')->nullable(false)->change();
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->unsignedBigInteger('status_id')->nullable(false)->change();
            $table->unsignedBigInteger('importance_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('type_id')->nullable()->change();
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->unsignedBigInteger('status_id')->nullable()->change();
            $table->unsignedBigInteger('importance_id')->nullable()->change();
        });
    }
};
