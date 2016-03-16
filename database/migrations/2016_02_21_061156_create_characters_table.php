<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCharactersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->nullable()->unsigned();
			$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('realm_id')->nullable()->unsigned();
			$table->foreign('realm_id')->references('id')->on('realms')->onDelete('cascade');
            $table->integer('faction_id')->nullable()->unsigned();
			$table->foreign('faction_id')->references('id')->on('factions')->onDelete('cascade');
            $table->integer('race_id')->nullable()->unsigned();
			$table->foreign('race_id')->references('id')->on('races')->onDelete('cascade');
			$table->integer('level');
			$table->string('name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('characters');
    }
}
