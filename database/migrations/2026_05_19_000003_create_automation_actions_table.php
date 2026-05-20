<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_rule_id')->constrained()->cascadeOnDelete();
            $table->string('type', 100);
            $table->integer('sort_order')->default(0);
            $table->json('config_json');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_actions');
    }
};
