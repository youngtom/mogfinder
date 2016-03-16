<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddImportFlagFieldsToItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->boolean('imported_from_bnet')->default(0)->after('allowable_races');
            $table->boolean('transmoggable')->default(0)->after('imported_from_bnet');
            $table->boolean('imported_from_game')->default(0)->after('transmoggable');
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
            $table->dropColumn('imported_from_bnet');
            $table->dropColumn('transmoggable');
            $table->dropColumn('imported_from_game');
        });
    }
}
