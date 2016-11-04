<?php

namespace App\Http\Controllers;

use Blockchain\Blockchain;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\BlockchainApi;

class BlockchainController extends Controller
{

    // http://telegram.loc/create-wallet?chat_id=-63327814&admin_id=97440540
    public function createWallet(Request $request)
    {
        $chatId  = $request->get('chat_id');// -63327814
        $adminId = $request->get('admin_id');// 97440540

        print_r(BlockchainApi::createWallet($chatId, $adminId));
    }

    public function getBalance(Request $request)
    {
        $chatId = $request->get('chat_id');

        print_r(BlockchainApi::getBalance($chatId));
    }

    public function deleteWallet(Request $request)
    {
        $chatId = $request->get('chat_id');

        echo BlockchainApi::archiveWallet($chatId);
    }

    public function getAddress(Request $request)
    {
        $chatId = $request->get('chat_id');

        var_dump(BlockchainApi::getAddresses($chatId));
    }

    public function sendFunds(Request $request)
    {
        $chatId = $request->get('chat_id');
        $receiver = $request->get('receiver_wallet_addr');
        $amount = $request->get('amount', 0);

        echo BlockchainApi::sendFunds($chatId, $receiver, $amount);
    }
}
