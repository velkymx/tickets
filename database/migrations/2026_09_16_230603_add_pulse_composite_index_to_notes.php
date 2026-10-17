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
        Schema::table('notes', function (Blueprint $table) {
            // TicketPulseService loads a ticket's notes and filters by notetype
            // and resolved; this composite lets that query use an index instead
            // of scanning every note on the ticket.
            $table->index(['ticket_id', 'notetype', 'resolved'], 'notes_ticket_notetype_resolved_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropIndex('notes_ticket_notetype_resolved_index');
        });
    }
};
