<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWowdbFieldsToItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
	        $table->boolean('imported_from_wowdb')->default(0);
	    	$table->integer('wowdb_source_type')->nullable()->after('imported_from_game');
	    	$table->integer('wowdb_source_id')->nullable()->after('imported_from_game');
        	$table->integer('wowdb_source')->nullable()->after('imported_from_game');
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
	        $table->dropColumn('imported_from_wowdb');
            $table->dropColumn('wowdb_source_type');
            $table->dropColumn('wowdb_source_id');
            $table->dropColumn('wowdb_source');
        });
    }
}
