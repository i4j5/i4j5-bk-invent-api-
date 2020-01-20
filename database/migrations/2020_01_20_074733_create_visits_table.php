<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVisitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('first_visit');
            
            $table->string('google_client_id');
            $table->string('metrika_client_id');
            
            $table->string('landing_page');
            $table->string('referrer');
            
            $table->string('utm_sourse');
            $table->string('utm_medium');
            $table->string('utm_campaign');
            $table->string('utm_term');
            $table->string('utm_content');
            
            $table->text('trace');
            
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
        Schema::dropIfExists('visits');
    }
}
