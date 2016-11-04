<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TelegramChatsDeleteChatId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('telegram_chats', function (Blueprint $table) {
            $table->dropColumn('chat_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('telegram_chats', function (Blueprint $table) {
            $table->integer('chat_id');
        });
    }
}
