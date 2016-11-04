<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Telegram\Bot\Api;
use App\Models\Commands;

class MarkupBuilder extends Model {

    /**
     * @var Api
     */
    protected $telegram;

    public function __construct(Api $telegram) {
        $this->telegram = $telegram;
    }

    /**
     * @param bool|false $walletExist
     * @return string
     */
    public function getMarkupInit($walletExist = false) {
        $walletBtn = $walletExist ? Commands::COMMAND_TITLE_USE_CURRENT_WALLET : Commands::COMMAND_TITLE_CREATE_WALLET;

        $keyboard = [
            [$walletBtn],
            [Commands::COMMAND_TITLE_ABOUT],
        ];

        $replyMarkup = $this->telegram->replyKeyboardMarkup([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => false,
        ]);

        return $replyMarkup;
    }

    /**
     * @return string
     */
    public function getMarkupDefault() {
        $keyboard = [
            [Commands::COMMAND_TITLE_MENU],
            [Commands::COMMAND_TITLE_BACK],
        ];

        $replyMarkup = $this->telegram->replyKeyboardMarkup([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => false,
        ]);

        return $replyMarkup;
    }

    /**
     * @return string
     */
    public function getMarkupDefaultSingle() {
        $keyboard = [
            [Commands::COMMAND_TITLE_MENU],
        ];

        $replyMarkup = $this->telegram->replyKeyboardMarkup([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => false,
        ]);

        return $replyMarkup;
    }

    /**
     * @return string
     */
    public function getMarkUpWalletCreated() {
        $keyboard = [
            [Commands::COMMAND_TITLE_SEND],
            [Commands::COMMAND_TITLE_WALLET_INFO],
            [Commands::COMMAND_TITLE_ABOUT],
            [Commands::COMMAND_TITLE_MENU],
        ];

        $replyMarkup = $this->telegram->replyKeyboardMarkup([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => false,
        ]);

        return $replyMarkup;
    }

    /**
     * @return string
     */
    public function getMarkUpConfirmPayment() {
        $keyboard = [
            [Commands::COMMAND_TITLE_YES],
            [Commands::COMMAND_TITLE_NO],
            [Commands::COMMAND_TITLE_BACK],
            [Commands::COMMAND_TITLE_MENU],
        ];

        $replyMarkup = $this->telegram->replyKeyboardMarkup([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => false,
        ]);

        return $replyMarkup;
    }

    /**
     * @return string
     */
    public function getMarkupEmpty() {
        return $this->telegram->replyKeyboardHide();
    }
}
