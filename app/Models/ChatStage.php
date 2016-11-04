<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatStage extends Model
{
    const STAGE_INIT            = 'init';
    const STAGE_WALLET_CREATED  = 'wallet_created';
    const STAGE_MAKE_PAYMENT_ENTER_WALLET_ADDRESS   = 'make_payment_enter_wallet_address';
    const STAGE_MAKE_PAYMENT_ENTER_AMOUNT           = 'make_payment_enter_amount';
    const STAGE_CONFIRM_PAYMENT                     = 'confirm_payment';
    
    protected $table = 'chat_stage';

    protected $fillable = ['id','data'];

    /**
     * @return array
     */
    public static function getAllowedStages() {
        return [
            self::STAGE_INIT,
            self::STAGE_WALLET_CREATED,
            self::STAGE_MAKE_PAYMENT_ENTER_WALLET_ADDRESS,
            self::STAGE_MAKE_PAYMENT_ENTER_AMOUNT,
            self::STAGE_CONFIRM_PAYMENT,
        ];
    }
}
