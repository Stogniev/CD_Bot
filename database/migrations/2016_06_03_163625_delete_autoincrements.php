<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteAutoincrements extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            $table->integer('id')->unique()->change();
        });

        Schema::table('telegram_chats', function (Blueprint $table) {
            $table->integer('id')->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            $table->increments('id')->change();
        });

        Schema::table('telegram_chats', function (Blueprint $table) {
            $table->increments('id')->change();
        });
    }
}
