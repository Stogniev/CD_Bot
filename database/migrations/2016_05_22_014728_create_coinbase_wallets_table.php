<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCoinbaseWalletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /*Schema::create('coinbase_wallets', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('owner');
            $table->char('ext_id');
            $table->char('name');
            $table->char('master');
            $table->char('key');
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
        //Schema::drop('coinbase_wallets');
    }
}
