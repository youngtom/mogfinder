<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUsableByAllClassesFieldToInventoryTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inventory_types', function (Blueprint $table) {
            $table->boolean('usable_by_all_classes')->after('transmoggable')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inventory_types', function (Blueprint $table) {
            $table->dropColumn('usable_by_all_classes');
        });
    }
}
