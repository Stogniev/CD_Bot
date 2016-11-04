<?php
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-bot/', 'HookController@testBotAction');
Route::get('/get-updates/', 'HookController@getUpdatesAction');
Route::get('/set-webhook/', 'HookController@setWebhookAction');
Route::get('/remove-webhook/', 'HookController@removeWebhookAction');
Route::post('/' . env('TELEGRAM_BOT_TOKEN') . '/webhook', 'HookController@webhookAction');

Route::get('/create-wallet/', 'BlockchainController@createWallet');
Route::get('/delete-wallet/', 'BlockchainController@deleteWallet');
Route::get('/get-address/', 'BlockchainController@getAddress');
Route::get('/get-balance/', 'BlockchainController@getBalance');
Route::get('/send-funds/', 'BlockchainController@sendFunds');
