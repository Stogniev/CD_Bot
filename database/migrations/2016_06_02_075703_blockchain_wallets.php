<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BlockchainWallets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('blockchain_wallets', function (Blueprint $table) {
            $table->increments('id');
            $table->char('guid');
            $table->char('address');
            $table->char('password');
            $table->char('chat_id');
            $table->boolean('is_deleted')->default(false);
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
        Schema::drop('blockchain_wallets');
    }
}
