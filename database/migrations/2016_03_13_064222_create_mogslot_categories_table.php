<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMogslotCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mogslot_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('group', ['armor', 'weapons', 'misc']);
            $table->string('name')->nullable();
            $table->string('label')->nullable();
            $table->string('url_token')->nullable();
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
        Schema::drop('mogslot_categories');
    }
}
