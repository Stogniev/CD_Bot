<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BlockchainWalletsChatIdUnique extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('blockchain_wallets', function (Blueprint $table) {
            $table->integer('chat_id')->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('blockchain_wallets', function (Blueprint $table) {
            $table->integer('chat_id')->change();
        });
    }
}
