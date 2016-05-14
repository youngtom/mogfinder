<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPluralLabelFieldToItemSourceTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_source_types', function (Blueprint $table) {
            $table->string('plural_label')->nullable()->after('simple_label');
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
            $table->dropColumn('plural_label');
        });
    }
}
