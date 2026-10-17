<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('notification_preferences')->nullable();
        });

        foreach (['notify_mentions' => 'mentions', 'notify_replies' => 'replies', 'notify_watching' => 'watching'] as $column => $key) {
            if (! Schema::hasColumn('users', $column)) {
                continue;
            }

            foreach (DB::table('users')->select('id', $column)->cursor() as $row) {
                $current = (array) (DB::table('users')->where('id', $row->id)->value('notification_preferences') ?? []);
                $current[$key] = (bool) $row->{$column};
                DB::table('users')->where('id', $row->id)->update(['notification_preferences' => json_encode($current)]);
            }

            Schema::table('users', function (Blueprint $table) use ($column) {
                $table->dropColumn($column);
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_mentions')->default(true);
            $table->boolean('notify_replies')->default(true);
            $table->boolean('notify_watching')->default(true);
        });

        foreach (['mentions' => 'notify_mentions', 'replies' => 'notify_replies', 'watching' => 'notify_watching'] as $key => $column) {
            foreach (DB::table('users')->select('id', 'notification_preferences')->cursor() as $row) {
                $prefs = json_decode((string) $row->notification_preferences, true) ?? [];
                DB::table('users')->where('id', $row->id)->update([$column => (bool) ($prefs[$key] ?? true)]);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notification_preferences');
        });
    }
};
