<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('note_attachments', function (Blueprint $table) {
            $table->unsignedBigInteger('size')->change();
        });
    }

    public function down(): void
    {
        Schema::table('note_attachments', function (Blueprint $table) {
            $table->unsignedInteger('size')->change();
        });
    }
};
