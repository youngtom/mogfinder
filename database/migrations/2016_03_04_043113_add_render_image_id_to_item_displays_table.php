<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRenderImageIdToItemDisplaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_displays', function (Blueprint $table) {
            
            $table->integer('render_image_id')->nullable()->unsigned()->after('bnet_display_id');
			$table->foreign('render_image_id')->references('id')->on('file_uploads')->onDelete('cascade');
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
            $table->dropColumn('render_image_id');
        });
    }
}
