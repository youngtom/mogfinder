<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateZonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('zones', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('parent_zone_id')->nullable()->unsigned();
			$table->foreign('parent_zone_id')->references('id')->on('zones')->onDelete('cascade');
			$table->integer('bnet_id');
			$table->string('name');
			$table->string('url_token');
			$table->integer('expansion')->nullable();
			$table->boolean('is_dungeon')->default(0);
			$table->boolean('is_raid')->default(0);
			$table->string('available_modes')->nullable();
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
        Schema::drop('zones');
    }
}
