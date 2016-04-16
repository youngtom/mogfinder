<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSingularLabelFieldToMogslotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mogslots', function (Blueprint $table) {
            $table->string('singular_label')->after('simple_label')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mogslots', function (Blueprint $table) {
            $table->dropColumn('singular_label');
        });
    }
}
