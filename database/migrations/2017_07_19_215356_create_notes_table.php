<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('notes', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('body');
            $table->integer('user_id')->unsigned();
            $table->integer('ticket_id')->unsigned();
            $table->integer('hide')->default(0);
            $table->timestamps();

            // $table->foreign('user_id')->references('id')->on('users');
            // $table->foreign('ticket_id')->references('id')->on('tickets');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('notes');
    }
}
