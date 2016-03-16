<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('bnet_id');
            $table->integer('item_context_id')->nullable()->unsigned();
			$table->foreign('item_context_id')->references('id')->on('item_contexts')->onDelete('cascade');
			$table->string('bonus')->nullable();
            $table->integer('item_display_id')->nullable()->unsigned();
			$table->foreign('item_display_id')->references('id')->on('item_displays')->onDelete('cascade');
            $table->string('name');
            $table->integer('item_bind')->nullable();
            $table->integer('buy_price');
            $table->integer('sell_price');
            $table->integer('item_type_id')->nullable()->unsigned();
			$table->foreign('item_type_id')->references('id')->on('item_types')->onDelete('cascade');
            $table->integer('item_subtype_id')->nullable()->unsigned();
			$table->foreign('item_subtype_id')->references('id')->on('item_subtypes')->onDelete('cascade');
            $table->integer('inventory_type_id')->nullable()->unsigned();
			$table->foreign('inventory_type_id')->references('id')->on('inventory_types')->onDelete('cascade');
			$table->boolean('equippable');
			$table->integer('item_level')->nullable();
			$table->integer('quality');
			$table->integer('required_level')->nullable();
            $table->integer('item_source_id')->nullable()->unsigned();
			$table->foreign('item_source_id')->references('id')->on('item_sources')->onDelete('cascade');
			$table->boolean('auctionable');
			$table->string('allowable_classes')->nullable();
			$table->string('allowable_races')->nullable();
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
        Schema::drop('items');
    }
}
