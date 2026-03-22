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
        Schema::table('notes', function (Blueprint $blueprint) {
            $blueprint->unsignedInteger('parent_id')->nullable()->after('ticket_id');

            $blueprint->foreign('parent_id')->references('id')->on('notes')->onDelete('cascade');
            $blueprint->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notes', function (Blueprint $blueprint) {
            $blueprint->dropForeign(['parent_id']);
            $blueprint->dropIndex(['parent_id']);
            $blueprint->dropColumn('parent_id');
        });
    }
};
