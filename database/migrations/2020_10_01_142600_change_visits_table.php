<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeVisitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::create('visits', function (Blueprint $table) {
        //     $table->text('utm_source')->change();
        //     $table->text('utm_medium')->change();
        //     $table->text('utm_campaign')->change();
        //     $table->text('utm_term')->change();
        //     $table->text('utm_content')->change();
        // });

        DB::statement('ALTER TABLE visits MODIFY utm_source TEXT;');
        DB::statement('ALTER TABLE visits MODIFY utm_medium TEXT;');
        DB::statement('ALTER TABLE visits MODIFY utm_campaign TEXT;');
        DB::statement('ALTER TABLE visits MODIFY utm_term TEXT;');
        DB::statement('ALTER TABLE visits MODIFY utm_content TEXT;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::table('visits', function (Blueprint $table) {
        //     $table->string('utm_source')->change();
        //     $table->string('utm_medium')->change();
        //     $table->string('utm_campaign')->change();
        //     $table->string('utm_term')->change();
        //     $table->string('utm_content')->change();
        // });

        //DB::statement('ALTER TABLE visits MODIFY utm_source STRING(255);');
        // DB::statement('ALTER TABLE visits MODIFY utm_medium STRING(255);');
        // DB::statement('ALTER TABLE visits MODIFY utm_campaign STRING(255);');
        // DB::statement('ALTER TABLE visits MODIFY utm_term STRING(255);');
        // DB::statement('ALTER TABLE visits MODIFY utm_content STRING(255);');
    }
}
