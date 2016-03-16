<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSourceDisplayFieldsToItemSourceTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_source_types', function (Blueprint $table) {
            $table->string('simple_label')->nullable()->after('label');
            $table->string('context_label')->nullable()->after('label');
            $table->string('wowhead_link_format')->nullable()->after('simple_label');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('item_source_types', function (Blueprint $table) {
            $table->dropColumn('simple_label');
            $table->dropColumn('context_label');
            $table->dropColumn('wowhead_link_format');
        });
    }
}
