<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIconImageIdToItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->integer('icon_image_id')->nullable()->unsigned()->after('item_display_id');
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
        Schema::table('item_displays', function (Blueprint $table) {
            $table->dropColumn('icon_image_id');
        });
    }
}
