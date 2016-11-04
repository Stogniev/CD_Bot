<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramChats extends Model
{
    protected $table = 'telegram_chats';

    protected $fillable = ['id'];

    /**
     * @return \BlockchainWallets
     */
    public function getWallet()
    {
        return BlockchainWallets::where('chat_id', $this->id)
            ->where('is_deleted', 0)
            ->first();
    }
}
