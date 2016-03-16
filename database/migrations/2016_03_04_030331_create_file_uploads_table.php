<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFileUploadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('file_uploads', function (Blueprint $table) {
            $table->increments('id');
            $table->string('path');
            $table->string('filename');
            $table->string('filetype');
            $table->integer('filesize');
            $table->string('description');
            $table->string('token');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('resize_width')->nullable();
            $table->integer('resize_height')->nullable();
            $table->string('resize_method')->nullable();
            $table->integer('parent_file_id')->nullable()->unsigned();
			$table->foreign('parent_file_id')->references('id')->on('file_uploads')->onDelete('cascade');
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
        Schema::drop('file_uploads');
    }
}
