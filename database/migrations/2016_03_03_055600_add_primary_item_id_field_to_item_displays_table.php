<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPrimaryItemIdFieldToItemDisplaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_displays', function (Blueprint $table) {
            $table->integer('primary_item_id')->nullable()->unsigned();
			$table->foreign('primary_item_id')->references('id')->on('items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('item_displays', function (Blueprint $table) {
            $table->dropColumn('primary_item_id');
        });
    }
}
