<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateZoneCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('zone_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('group', ['zones', 'dungeons', 'raids']);
            $table->string('name');
            $table->string('url_token');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('zone_categories');
    }
}
