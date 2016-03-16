<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexesToUserItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_items', function (Blueprint $table) {
            $table->index('item_id');
            $table->index('item_display_id');
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
            $table->dropIndex('item_id');
            $table->dropIndex('item_display_id');
        });
    }
}
