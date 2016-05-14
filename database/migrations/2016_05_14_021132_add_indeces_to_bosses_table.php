<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndecesToBossesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bosses', function (Blueprint $table) {
            $table->index('bnet_id');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bosses', function (Blueprint $table) {
            $table->dropIndex(['bnet_id']);
            $table->dropIndex(['name']);
        });
    }
}
