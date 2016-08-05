<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProgressNewFieldToUserDatafilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_datafiles', function (Blueprint $table) {
            $table->integer('progress_new')->after('progress_total')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_datafiles', function (Blueprint $table) {
            $table->dropColumn('progress_new');
        });
    }
}
