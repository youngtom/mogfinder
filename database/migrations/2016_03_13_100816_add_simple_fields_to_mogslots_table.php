<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSimpleFieldsToMogslotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mogslots', function (Blueprint $table) {
            $table->string('simple_label')->after('label')->nullable();
            $table->string('simple_url_token')->after('url_token')->nullable();
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
            $table->dropColumn('simple_label');
            $table->dropColumn('simple_url_token');
        });
    }
}
