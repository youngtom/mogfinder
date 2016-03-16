<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddItemSourceTypeIdFieldToItemSourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_sources', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->integer('item_source_type_id')->nullable()->unsigned()->after('bnet_source_id');
			$table->foreign('item_source_type_id')->references('id')->on('item_source_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('item_sources', function (Blueprint $table) {
            $table->string('type')->after('bnet_source_id');
        });
    }
}
