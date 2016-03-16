<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->nullable()->unsigned();
			$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('item_id')->nullable()->unsigned();
			$table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->integer('character_id')->nullable()->unsigned();
			$table->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
			$table->integer('item_location_id')->nullable()->unsigned();
			$table->foreign('item_location_id')->references('id')->on('item_locations')->onDelete('cascade');
			$table->integer('bound')->nullable();
			$table->string('item_link')->nullable();
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
        Schema::drop('user_items');
    }
}
