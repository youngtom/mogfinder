<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddItemSubtypeAndInventoryFieldsToItemDisplaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_displays', function (Blueprint $table) {
            $table->integer('inventory_type_id')->nullable()->unsigned()->after('bnet_display_id');
			$table->foreign('inventory_type_id')->references('id')->on('inventory_types')->onDelete('cascade');
            $table->integer('item_subtype_id')->nullable()->unsigned()->after('bnet_display_id');
			$table->foreign('item_subtype_id')->references('id')->on('item_subtypes')->onDelete('cascade');
			$table->boolean('transmoggable')->default(0)->after('bnet_display_id');
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
            $table->dropColumn('inventory_type_id');
            $table->dropColumn('item_subtype_id');
            $table->dropColumn('transmoggable');
        });
    }
}
