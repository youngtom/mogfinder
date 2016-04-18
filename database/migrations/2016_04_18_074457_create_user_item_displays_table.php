<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserItemDisplaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_item_displays', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->nullable()->unsigned();
			$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('item_display_id')->nullable()->unsigned();
			$table->foreign('item_display_id')->references('id')->on('item_displays')->onDelete('cascade');
			$table->integer('restricted_classes')->nullable();
			$table->integer('restricted_races')->nullable();
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
        Schema::drop('user_item_displays');
    }
}
