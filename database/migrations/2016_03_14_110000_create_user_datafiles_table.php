<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserDatafilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_datafiles', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->nullable()->unsigned();
			$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
			$table->integer('file_id')->nullable()->unsigned();
			$table->foreign('file_id')->references('id')->on('file_uploads')->onDelete('cascade');
			$table->string('md5')->nullable();
			$table->string('token')->nullable();
			$table->mediumText('import_data')->nullable();
			$table->text('response')->nullable();
			$table->integer('status')->default(0);
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
        Schema::drop('user_datafiles');
    }
}
