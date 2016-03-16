<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveSourceFieldsFromItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('item_source_id');
            $table->dropForeign('items_item_source_id_foreign');
            $table->dropColumn('wowdb_source');
            $table->dropColumn('wowdb_source_type');
            $table->dropColumn('wowdb_source_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->integer('item_source_id')->nullable()->unsigned()->after('required_level');
			$table->foreign('item_source_id')->references('id')->on('item_sources')->onDelete('cascade');
	    	$table->integer('wowdb_source_type')->nullable()->after('imported_from_game');
	    	$table->integer('wowdb_source_id')->nullable()->after('imported_from_game');
        	$table->integer('wowdb_source')->nullable()->after('imported_from_game');
        });
    }
}
