<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Blockchain\Blockchain;
use \LinusU\Bitcoin\AddressValidator;
use App\Models\TelegramChats;

class BlockchainApi extends Model
{
    const BTC_CURRENCY = 'BTC';

    const WRONG_WALLET = 'Given wallet not exists';
    const WRONG_RECEIVER = 'Wrong address given';
    const NO_CHAT = 'Chat not exists';
    const NO_WALLET = 'No wallet in chat';

    const SUCCESS_DELETE = 'Deleted';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'blockchain_wallets';

    /**
     * @var Blockchain
     */
    protected $blockchain;

    /**
     * @param bool|false $payments
     * @return Blockchain
     */
    private static function getApi($payments = false)
    {
        $apiKey     = $payments ? env('BLOCKCHAIN_PAYMENTS')
            : env('BLOCKCHAIN_WALLET');
        $blockchain = new Blockchain($apiKey);
        $blockchain->setServiceUrl(env('NODE_SERVER_URL'));

        return $blockchain;
    }

    /**
     * Return walletAddress if exist
     *
     * @param $chatId
     * @param $adminId
     * @return string
     */
    public static function createWallet($chatId, $adminId)
    {
        $admin = TelegramUsers::find($adminId);

        if (empty($admin)) {
            $admin          = new TelegramUsers();
            $admin->id = $adminId;
            $admin->save();
        }

        $chat = TelegramChats::find($chatId);

        if (!empty($chat) && !empty($wallet = $chat->getWallet())) {
            return $wallet->address;
        }

        if (empty($chat)) {
            $chat = new TelegramChats();
            $chat->id = $chatId;
            $chat->save();
        }

        $wallet           = new BlockchainWallets();
        $wallet->password = str_random(rand(16, 19));
        $wallet->chat_id  = $chatId;

        try {
            $blockchain       = self::getApi();
            $blockchainWallet = $blockchain->Create->create($wallet->password);
        } catch (\Exception $ex) {
            return self::getError($ex);
        }

        $wallet->guid    = $blockchainWallet->guid;
        $wallet->address = $blockchainWallet->address;
        $wallet->save();

        return $wallet->address;
    }

    /**
     * @param $chatId
     * @return string
     */
    public static function getBalance($chatId)
    {
        $chat = TelegramChats::find($chatId);
        if (empty($chat)) {
            return self::NO_CHAT;
        }
        $wallet = $chat->getWallet();
        if (empty($wallet)) {
            return self::NO_WALLET;
        }
        try {
            $blockchain = self::getApi();
            $blockchain->Wallet->credentials($wallet->guid, $wallet->password);

            return $blockchain->Wallet->getBalance().' '.self::BTC_CURRENCY;
        } catch (\Exception $ex) {
            return self::getError($ex);
        }
    }

    /**
     * @param $chatId
     * @return string
     */
    public static function archiveWallet($chatId)
    {
        $chat = TelegramChats::find($chatId);
        if (empty($chat)) {
            return self::NO_CHAT;
        }
        $wallet = $chat->getWallet();

        if (empty($wallet)) {
            return self::NO_WALLET;
        }

        try {
            $blockchain = self::getApi();
            $blockchain->Wallet->credentials($wallet->guid, $wallet->password);
            $blockchain->Wallet->archiveAddress($wallet->address);

            $wallet->is_deleted = 1;
            $wallet->save();

            return self::SUCCESS_DELETE;
        } catch (\Exception $ex) {
            return self::getError($ex);
        }
    }

    /**
     * @param $chatId
     * @return array|string
     */
    public static function getAddresses($chatId)
    {
        $chat = TelegramChats::find($chatId);
        if (empty($chat)) {
            return self::NO_CHAT;
        }
        $wallet = $chat->getWallet();
        if (empty($wallet)) {
            return self::NO_WALLET;
        }

        try {
            $blockchain = self::getApi();
            $blockchain->Wallet->credentials($wallet->guid, $wallet->password);
            $addresses = $blockchain->Wallet->getAddresses();

            return $addresses;
        } catch (\Exception $ex) {
            return self::getError($ex);
        }
    }

    /**
     * @param $chatId
     * @param $receiver
     * @param $amount
     * @return string
     * @throws \LinusU\Bitcoin\Exception
     */
    public static function sendFunds($chatId, $receiver, $amount)
    {
        $chat = TelegramChats::find($chatId);
        if (empty($chat)) {
            return self::NO_CHAT;
        }
        $wallet = $chat->getWallet();
        if (empty($wallet)) {
            return self::NO_WALLET;
        }
        if (!AddressValidator::isValid($receiver)) {
            return self::WRONG_RECEIVER;
        }

        try {
            $blockchain = self::getApi();
            $blockchain->Wallet->credentials($wallet->guid, $wallet->password);
            $send = $blockchain->Wallet->send(
                $receiver,
                $amount,
                $wallet->address
            );

            return $send->message; // TODO: check if result = success
        } catch (\Exception $ex) {
            return self::getError($ex);
        }
    }

    /**
     * @param \Exception $ex
     * @return string
     */
    private static function getError(\Exception $ex)
    {
        $php_errormsg = preg_replace('/.+?(?=:)/', '', $ex->getMessage());

        return trim(str_replace(':', '', $php_errormsg));
    }
}