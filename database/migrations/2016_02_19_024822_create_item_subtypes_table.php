<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateItemSubtypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_subtypes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('bnet_id');
            $table->integer('item_type_id')->nullable()->unsigned();
			$table->foreign('item_type_id')->references('id')->on('item_types')->onDelete('cascade');
			$table->string('name');
			$table->string('name_full')->nullable();
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
        Schema::drop('item_subtypes');
    }
}
