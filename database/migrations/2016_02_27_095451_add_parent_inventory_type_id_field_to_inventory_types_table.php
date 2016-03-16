<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddParentInventoryTypeIdFieldToInventoryTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inventory_types', function (Blueprint $table) {
           $table->integer('parent_inventory_type_id')->after('usable_by_all_classes')->nullable(); 
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
            $table->dropColumn('parent_inventory_type_id');
        });
    }
}
