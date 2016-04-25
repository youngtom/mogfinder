<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBossesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bosses', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('zone_id')->nullable()->unsigned();
			$table->foreign('zone_id')->references('id')->on('zones')->onDelete('cascade');
			$table->integer('parent_boss_id')->nullable()->unsigned();
			$table->foreign('parent_boss_id')->references('id')->on('bosses')->onDelete('cascade');
			$table->integer('bnet_id');
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
        Schema::drop('bosses');
    }
}
