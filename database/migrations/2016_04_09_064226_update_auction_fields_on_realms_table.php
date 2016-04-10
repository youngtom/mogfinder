<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAuctionFieldsOnRealmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('realms', function (Blueprint $table) {
			$table->dropColumn('latest_auction_data');
			$table->integer('parent_realm_id')->nullable()->unsigned()->after('id');
			$table->foreign('parent_realm_id')->references('id')->on('realms')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('realms', function (Blueprint $table) {
            $table->longText('latest_auction_data')->nullable()->after('region');
            $table->dropColumn('parent_realm_id');
        });
    }
}
