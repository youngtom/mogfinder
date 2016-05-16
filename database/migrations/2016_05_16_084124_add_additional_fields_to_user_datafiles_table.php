<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAdditionalFieldsToUserDatafilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_datafiles', function (Blueprint $table) {
            $table->integer('progress_total')->default(0)->after('response');
            $table->integer('progress_current')->default(0)->after('response');
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
            $table->dropColumn('progress_current');
            $table->dropColumn('progress_total');
        });
    }
}
