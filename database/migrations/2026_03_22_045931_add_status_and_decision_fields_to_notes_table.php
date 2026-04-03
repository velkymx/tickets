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
            $blueprint->boolean('pinned')->default(false)->after('edited_at');
            $blueprint->boolean('resolved')->default(false)->after('pinned');
            $blueprint->unsignedBigInteger('resolved_by')->nullable()->after('resolved');
            $blueprint->unsignedInteger('supersedes_id')->nullable()->after('resolved_by');
            $blueprint->text('resolution_message')->nullable()->after('supersedes_id');

            $blueprint->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
            $blueprint->foreign('supersedes_id')->references('id')->on('notes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notes', function (Blueprint $blueprint) {
            $blueprint->dropForeign(['resolved_by']);
            $blueprint->dropForeign(['supersedes_id']);
            $blueprint->dropColumn(['pinned', 'resolved', 'resolved_by', 'supersedes_id', 'resolution_message']);
        });
    }
};
