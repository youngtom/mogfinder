<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUsableByClassesBitmaskFieldToItemSubtypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_subtypes', function (Blueprint $table) {
            $table->integer('usable_by_classes_bitmask')->nullable()->after('name_full');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('item_subtypes', function (Blueprint $table) {
            $table->dropColumn('usable_by_classes_bitmask');
        });
    }
}
