<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyMilestoneData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

      Schema::table('milestones', function (Blueprint $table) {

          $table->text('description')->nullable();
          $table->dateTime('start_at')->nullable();
          $table->dateTime('due_at')->nullable();
          $table->dateTime('end_at')->nullable();

      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
