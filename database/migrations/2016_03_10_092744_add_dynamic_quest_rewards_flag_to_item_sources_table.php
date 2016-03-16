<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDynamicQuestRewardsFlagToItemSourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_sources', function (Blueprint $table) {
            $table->boolean('dynamic_quest_rewards')->default(0)->after('import_source');
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
            $table->dropColumn('dynamic_quest_rewards');
        });
    }
}
