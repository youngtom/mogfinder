<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuctionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auctions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('bnet_id');
            $table->integer('item_id')->nullable()->unsigned();
			$table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->integer('item_display_id')->nullable()->unsigned();
			$table->foreign('item_display_id')->references('id')->on('item_displays')->onDelete('cascade');
			$table->integer('realm_id')->nullable()->unsigned();
			$table->foreign('realm_id')->references('id')->on('realms')->onDelete('cascade');
			$table->string('seller');
			$table->integer('bid')->nullable();
			$table->integer('buyout')->nullable();
			$table->string('timeleft')->nullable();
			$table->string('bonuses')->nullable();
			$table->boolean('updated')->default(0);
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
        Schema::drop('auctions');
    }
}
