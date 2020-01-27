<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            $table->string('deal_id', 255);
            $table->string('visitor_id', 255);
            $table->string('session_id', 255);
            $table->string('hit_id', 255);
            
            $table->string('hash_id', 255);
            
            $table->string('name', 255);
            $table->string('phone', 255);
            $table->string('email', 255);
            
            $table->string('title', 255);
            $table->text('url');
            $table->text('utm_medium');
            $table->text('utm_source');
            $table->text('utm_campaign');
            $table->text('utm_term');
            $table->text('utm_content');
            $table->text('comment');
            
            
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
        Schema::dropIfExists('leads');
    }
}
