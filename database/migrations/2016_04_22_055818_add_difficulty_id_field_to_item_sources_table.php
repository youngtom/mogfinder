<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDifficultyIdFieldToItemSourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_sources', function (Blueprint $table) {
            $table->integer('difficulty_id')->nullable()->unsigned()->after('zone_id');
			$table->foreign('difficulty_id')->references('id')->on('difficulties')->onDelete('cascade');
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
            $table->dropColumn('difficulty_id');
        });
    }
}
