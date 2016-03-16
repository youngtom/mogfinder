<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMogslotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mogslots', function (Blueprint $table) {
            $table->increments('id');
			$table->enum('category', ['armor', 'weapons', 'misc']);
			$table->string('label');
			$table->string('url_token')->nullable();
            $table->integer('inventory_type_id')->nullable()->unsigned();
			$table->foreign('inventory_type_id')->references('id')->on('inventory_types')->onDelete('cascade');
            $table->integer('item_subtype_id')->nullable()->unsigned();
			$table->foreign('item_subtype_id')->references('id')->on('item_subtypes')->onDelete('cascade');
			$table->integer('allowed_class_bitmask')->nullable();
			$table->boolean('cosmetic')->default(0);
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
        Schema::drop('mogslots');
    }
}
