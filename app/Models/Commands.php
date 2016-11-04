<?php

namespace App\Models;

class Commands {

    const COMMAND_TITLE_MENU                = 'Menu';
    const COMMAND_TITLE_BACK                = 'Back';
    const COMMAND_TITLE_ABOUT               = 'About';
    const COMMAND_TITLE_CREATE_WALLET       = 'Create wallet';
    const COMMAND_TITLE_USE_CURRENT_WALLET  = 'Use current wallet';
    const COMMAND_TITLE_SEND                = 'Send';
    const COMMAND_TITLE_WALLET_INFO         = 'Wallet info';
    const COMMAND_TITLE_YES                 = 'Yes';
    const COMMAND_TITLE_NO                  = 'No';

    /**
     * @return array
     */
    public static function getCommandsStart() {
        return ['/start', '/start@' . env('TELEGRAM_BOT_NAME')];
    }

    /**
     * @return array
     */
    public static function getCommandsStop() {
        return ['/stop', '/stop@' . env('TELEGRAM_BOT_NAME')];
    }

    /**
     * @return array
     */
    public static function getCommandsMenu() {
        return [self::COMMAND_TITLE_MENU];
    }

    /**
     * @return array
     */
    public static function getCommandsAbout() {
        return [self::COMMAND_TITLE_ABOUT];
    }

    /**
     * @return array
     */
    public static function getCommandsCreateWallet() {
        return [self::COMMAND_TITLE_CREATE_WALLET];
    }

    /**
     * @return array
     */
    public static function getCommandsUseCurrentWallet() {
        return [self::COMMAND_TITLE_USE_CURRENT_WALLET];
    }

    /**
     * @return array
     */
    public static function getCommandsSend() {
        return [self::COMMAND_TITLE_SEND];
    }

    /**
     * @return array
     */
    public static function getCommandsWalletInfo() {
        return [self::COMMAND_TITLE_WALLET_INFO];
    }
}