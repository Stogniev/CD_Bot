<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChatUsersDeleteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::drop('chat_users');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('chat_users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('chat_id');
            $table->integer('user_id');
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });
    }
}
