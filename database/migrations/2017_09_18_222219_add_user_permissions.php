<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('users', function (Blueprint $table) {

          $table->tinyInteger('active')->default(1);
          $table->tinyInteger('milestone')->default(1);
          $table->tinyInteger('ticket')->default(1);
          $table->tinyInteger('project')->default(1);
          $table->tinyInteger('report')->default(1);

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
