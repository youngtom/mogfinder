<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMogslotIdFieldToItemDisplaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_displays', function (Blueprint $table) {
            $table->integer('mogslot_id')->nullable()->unsigned()->after('transmoggable');
			$table->foreign('mogslot_id')->references('id')->on('mogslots')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('item_displays', function (Blueprint $table) {
            $table->dropColumn('mogslot_id');
        });
    }
}
