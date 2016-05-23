<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLocalizationFieldsToRealmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('realms', function (Blueprint $table) {
            $table->string('localized_name')->nullable()->after('name');
            $table->string('localized_url_token')->nullable()->after('url_token');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('realms', function (Blueprint $table) {
            $table->dropColumn('localized_name');
            $table->dropColumn('localized_url_token');
        });
    }
}
