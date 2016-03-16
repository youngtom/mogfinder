<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUrlTokenFieldToItemSourceTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_source_types', function (Blueprint $table) {
            $table->string('url_token')->nullable()->after('label');
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
            $table->dropColumn('url_token');
        });
    }
}
