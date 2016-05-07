<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddZoneRelevantFieldToItemSourceTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_source_types', function (Blueprint $table) {
            $table->boolean('zone_relevant')->default(0)->after('wowhead_link_format');
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
            $table->dropColumn('zone_relevant');
        });
    }
}
