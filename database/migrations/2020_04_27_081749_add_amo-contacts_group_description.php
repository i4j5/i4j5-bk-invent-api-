<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAmoContactsGroupDescription extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('amo-contacts', function (Blueprint $table) {
            $table->string('group');
            $table->text('description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('amo-contacts', function (Blueprint $table) {
            $table->dropColumn('group');
            $table->dropColumn('description');
        });
    }
}
