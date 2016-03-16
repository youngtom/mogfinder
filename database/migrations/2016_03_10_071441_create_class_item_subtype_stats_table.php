<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClassItemSubtypeStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('class_item_subtype_stats', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('class_id')->nullable()->unsigned();
			$table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
            $table->integer('item_subtype_id')->nullable()->unsigned();
			$table->foreign('item_subtype_id')->references('id')->on('item_subtypes')->onDelete('cascade');
            $table->string('stats')->nullable();
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
        Schema::drop('class_item_subtype_stats');
    }
}
