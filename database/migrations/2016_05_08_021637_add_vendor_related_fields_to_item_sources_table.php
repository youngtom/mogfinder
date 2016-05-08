<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVendorRelatedFieldsToItemSourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_sources', function (Blueprint $table) {
            $table->string('label')->nullable()->after('class_id');
            $table->text('item_currency_info')->nullable()->after('label');
            $table->bigInteger('currency_amount')->nullable()->after('label');
            $table->integer('currency_id')->nullable()->unsigned()->after('label');
			$table->foreign('currency_id')->references('id')->on('currencies')->onDelete('cascade');
            $table->bigInteger('gold_cost')->nullable()->after('label');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('item_sources', function (Blueprint $table) {
            $table->dropColumn('label');
            $table->dropColumn('gold_cost');
            $table->dropColumn('currency_id');
            $table->dropColumn('currency_amount');
            $table->dropColumn('item_currency_info');
        });
    }
}
