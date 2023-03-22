<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTicketViews extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('ticket_views', function (Blueprint $table) {
          $table->increments('id');
          $table->integer('ticket_id')->unsigned();
          $table->integer('user_id')->unsigned();
          $table->timestamps();

        //   $table->foreign('user_id')->references('id')->on('users');
        //   $table->foreign('ticket_id')->references('id')->on('tickets');
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('ticket_views');
    }
}
