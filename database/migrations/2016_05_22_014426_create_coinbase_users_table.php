<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCoinbaseUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /*Schema::create('coinbase_users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('wallet_id');
            $table->char('pubkey');
            $table->char('privkey');
            $table->char('address');
            $table->timestamps();
        });*/
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //Schema::drop('coinbase_users');
    }
}
