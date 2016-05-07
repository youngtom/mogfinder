<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateItemDisplayZonePivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_display_zone', function (Blueprint $table) {
            $table->integer('item_display_id')->unsigned()->index();
            $table->foreign('item_display_id')->references('id')->on('item_displays')->onDelete('cascade');
            $table->integer('zone_id')->unsigned()->index();
            $table->foreign('zone_id')->references('id')->on('zones')->onDelete('cascade');
            $table->primary(['item_display_id', 'zone_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('item_display_zone');
    }
}
