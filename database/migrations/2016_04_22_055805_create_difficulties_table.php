<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDifficultiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('difficulties', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('shortname')->nullable();
            $table->string('label')->nullable();
            $table->string('url_token')->nullable();
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
        Schema::drop('difficulties');
    }
}
