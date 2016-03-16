<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMogslotCategoryIdFieldToMogslotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mogslots', function (Blueprint $table) {
            $table->integer('mogslot_category_id')->nullable()->unsigned()->after('url_token');
			$table->foreign('mogslot_category_id')->references('id')->on('mogslot_categories')->onDelete('cascade');
			$table->dropColumn('category');
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
            $table->dropColumn('mogslot_category_id');
            $table->enum('category', ['armor', 'weapons', 'misc'])->after('id');
        });
    }
}
