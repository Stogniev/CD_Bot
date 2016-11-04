<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropWalletPassword extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
//        Schema::table('blockchain_wallets', function (Blueprint $table) {
//            $table->dropColumn('password');
//        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
//        Schema::table('blockchain_wallets', function (Blueprint $table) {
//            $table->char('password');
//        });
    }
}
