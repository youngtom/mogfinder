<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIconAndUrlTokenFieldsToClassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->integer('icon_image_id')->nullable()->unsigned()->after('name');
			$table->foreign('icon_image_id')->references('id')->on('file_uploads')->onDelete('cascade');
			$table->string('url_token')->after('name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('icon_image_id');
            $table->dropColumn('url_token');
        });
    }
}
