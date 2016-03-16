<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWowdbSourceTypeFieldToItemSourceTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_source_types', function (Blueprint $table) {
            $table->integer('wowdb_item_type')->after('label')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('item_source_types', function (Blueprint $table) {
            $table->dropColumn('wowdb_item_type');
        });
    }
}
