<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockchainWallets extends Model
{
    protected $table = 'blockchain_wallets';

    protected $fillable = ['guid', 'address', 'password', 'chat_id'];

    /**
     * @param $chatId
     * @return string
     */
    public function getAddressByChatId($chatId) {
        $result = self::where('chat_id', $chatId)->where('is_deleted', 0)->first();

        return !empty($result->address) ? $result->address : '';
    }
}
