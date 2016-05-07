<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFactionAndClassFieldsToItemSourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_sources', function (Blueprint $table) {
            $table->integer('class_id')->nullable()->unsigned()->after('difficulties');
			$table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
            $table->integer('faction_id')->nullable()->unsigned()->after('difficulties');
			$table->foreign('faction_id')->references('id')->on('factions')->onDelete('cascade');
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
            $table->dropColumn('class_id');
            $table->dropColumn('faction_id');
        });
    }
}
