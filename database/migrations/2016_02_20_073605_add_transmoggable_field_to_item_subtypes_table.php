<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTransmoggableFieldToItemSubtypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_subtypes', function (Blueprint $table) {
           $table->boolean('transmoggable')->default(0)->after('name_full');
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
            $table->dropColumn('transmoggable');
        });
    }
}
