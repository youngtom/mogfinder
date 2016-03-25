<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRaceBitmaskToFactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('factions', function (Blueprint $table) {
            $table->integer('icon_image_id')->nullable()->unsigned()->after('name');
			$table->foreign('icon_image_id')->references('id')->on('file_uploads')->onDelete('cascade');
            $table->integer('race_bitmask')->after('name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('factions', function (Blueprint $table) {
            $table->dropColumn('icon_image_id');
            $table->dropColumn('race_bitmask');
        });
    }
}
