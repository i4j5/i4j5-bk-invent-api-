<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type', 255);
            $table->integer('status');
            $table->text('text')->default(null);
            $table->text('media')->default(null);
            $table->text('thumbnail')->default(null);
            $table->text('file_name')->default(null);
            $table->integer('file_size')->default(0);
            $table->integer('timestamp');
            $table->string('amo_message_id', 255);
            $table->string('whatsapp_message_id', 255);
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
        Schema::dropIfExists('messages');
    }
}
