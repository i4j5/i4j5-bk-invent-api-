<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('playments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('order_id', 255);
            $table->integer('amount');
            $table->integer('status');
            $table->text('description')->default(null);
            $table->text('payment_url')->default(null);
            $table->integer('deal_id')->default(0);
            $table->text('fio')->default(null);
            $table->text('phone')->default(null);
            $table->text('email')->default(null);
            $table->timestamp('date');
            $table->timestamps();
        });

        DB::update("ALTER TABLE playments AUTO_INCREMENT = 87;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('playments');
    }
}
