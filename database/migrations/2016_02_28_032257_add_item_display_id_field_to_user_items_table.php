<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddItemDisplayIdFieldToUserItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_items', function (Blueprint $table) {
            $table->integer('item_display_id')->nullable()->unsigned()->after('item_id');
			$table->foreign('item_display_id')->references('id')->on('item_contexts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_items', function (Blueprint $table) {
            $table->dropColumn('item_display_id');
        });
    }
}
