<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCoinbaseWalletsAddressTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /*Schema::create('coinbase_wallets_address', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('wallet_id');
            $table->char('address_id');
            $table->char('address');
            $table->char('name');
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
        //Schema::drop('coinbase_wallets_address');
    }
}
