<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldsToItemSourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_sources', function (Blueprint $table) {
            $table->integer('item_id')->nullable()->unsigned()->after('id');
			$table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
			$table->string('import_source')->nullable()->after('item_source_type_id');
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
            $table->dropColum('item_id');
            $table->dropColum('import_source');
        });
    }
}