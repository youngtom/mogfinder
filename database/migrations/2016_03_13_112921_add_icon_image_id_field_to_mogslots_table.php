<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIconImageIdFieldToMogslotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mogslots', function (Blueprint $table) {
            $table->integer('icon_image_id')->nullable()->unsigned()->after('simple_url_token');
			$table->foreign('icon_image_id')->references('id')->on('file_uploads')->onDelete('cascade');
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
            $table->dropColumn('icon_image_id');
        });
    }
}
