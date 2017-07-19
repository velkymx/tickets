<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('tickets', function (Blueprint $table) {
          $table->increments('id');
          $table->string('subject');
          $table->string('description');
          $table->integer('type_id')->unsigned();
          $table->integer('user_id')->unsigned();
          $table->integer('status_id')->unsigned();
          $table->integer('importance_id')->unsigned();
          $table->integer('milestone_id')->unsigned();
          $table->integer('project_id')->unsigned();
          $table->integer('user_id2')->unsigned();
          $table->dateTime('due_at')->nullable();
          $table->dateTime('closed_at')->nullable();
          $table->timestamps();

          $table->foreign('user_id')->references('id')->on('users');
          $table->foreign('type_id')->references('id')->on('types');
          $table->foreign('status_id')->references('id')->on('statuses');
          $table->foreign('importance_id')->references('id')->on('importances');
          $table->foreign('milestone_id')->references('id')->on('milestones');
          $table->foreign('project_id')->references('id')->on('projects');
          $table->foreign('user_id2')->references('id')->on('users');
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('tickets');
    }
}
