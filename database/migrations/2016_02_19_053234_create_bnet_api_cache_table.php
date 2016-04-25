<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBnetApiCacheTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bnet_api_cache', function (Blueprint $table) {
            $table->increments('id');
            $table->string('endpoint');
            $table->string('request_uri');
			$table->longText('data');
			$table->integer('expiration');
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
        Schema::drop('bnet_api_cache');
    }
}
