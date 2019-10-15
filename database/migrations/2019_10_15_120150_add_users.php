<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('users')->insert([
            'name' => 'Евгений Калашников',
            'email' => 'dir@bkinvent.net',
            'password' => '',
            'role' => 10,
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
        ]);
        
        DB::table('users')->insert([
            'name' => 'Александр Сажин',
            'email' => 'sal@bkinvent.net',
            'password' => '',
            'role' => 10,
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        
    }
}
