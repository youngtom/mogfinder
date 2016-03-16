<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPrimaryStatsFieldToItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('primary_stats')->nullable()->after('allowable_races');
            $table->integer('bnet_itemset_id')->nullable()->after('primary_stats');
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
            $table->dropColumn('primary_stats');
            $table->dropColumn('bnet_itemset_id');
        });
    }
}
