<?php

namespace App\Http\Controllers;

use App\Models\BlockchainWallets;
use App\Models\Commands;
use Illuminate\Support\Facades\Session;
use Mockery\CountValidator\Exception;
use Telegram\Bot\Api;
use Illuminate\Http\Request;
use App\Models\MarkupBuilder;
use App\Models\ChatStage;
use App\Models\BlockchainApi;

use App\Http\Requests;

class HookController extends Controller
{
    const LOG_INFO      = 'INFO';
    const LOG_WARNING   = 'WARNING';
    const LOG_SUCCESS   = 'SUCCESS';
    const LOG_ERROR     = 'ERROR';

    const CHAT_TYPE_GROUP = 'group';

    /**
     * @var Api
     */
    protected $telegram;

    /**
     * @var null|string
     */
    protected $walletAddress;

    /**
     * @var MarkupBuilder
     */
    protected $markupBuilder;

    protected $stageInput;
    protected $stageOutput;

    protected $chatId = 0;
    protected $adminId = 0;
    protected $botName = '';

    public function __construct() {
        $this->telegram = $this->getTelegramInstance();
        $this->markupBuilder = new MarkupBuilder($this->telegram);

        define('TELEGRAM_API_URL', 'https://api.telegram.org/bot'. env('TELEGRAM_BOT_TOKEN') .'/');
    }

    /**
     * @return Api
     */
    protected function getTelegramInstance() {
        return new Api(env('TELEGRAM_BOT_TOKEN'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * Example of $request obj
        {
            "update_id":864688114,
            "message":{
                "message_id":560,
                "from":{
                    "id":97440540,
                    "first_name":"Stanislav",
                    "last_name":"Honcharenko",
                    "username":"StasHoncharenko"
                },
                "chat":{
                    "id":-137820493,
                    "title":"TestYandexBot",
                    "type":"group"
                },
                "date":1464537996,
                "reply_to_message":{
                    "message_id":545,
                    "from":{
                        "id":222943510,
                        "first_name":"ttt",
                        "username":"staging1_bot"
                    },
                    "chat":{
                        "id":-137820493,
                        "title":"TestYandexBot",
                        "type":"group"
                    },
                    "date":1464537903,
                    "text":"Hi, I'm CoinDriveBot! How can I help you?"
                },
                "text":"About"
            }
        }
     *
     * @return \Illuminate\Http\Response
     */
    public function webhookAction(Request $request) {
        $requestArr = $request->all();
        $this->writeInfoLogs('RequestObj: ', $requestArr);

        // set chatId
        $this->chatId = $this->getChatIdFromRequest($requestArr);

        $this->writeInfoLogs('ChatID=', $this->chatId);

        if ($this->chatId === 0) {
            $this->writeErrorLogs('chatId was not found');
            return;
        }

        $this->walletAddress = (new BlockchainWallets())->getAddressByChatId($this->chatId);

        $chatType = $this->getChatTypeFromRequest($requestArr);
        if ($chatType !== self::CHAT_TYPE_GROUP) {
            $this->writeErrorLogs('Sorry, chat type "' . $chatType . '"" not supported yet');
            return;
        }

        // init stage if doesn\'t exist
        $stageModel = ChatStage::find($this->chatId);

        if (!$stageModel) {
            $stageModel = new ChatStage();
            $stageModel->id = $this->chatId;
            $stageModel->data = json_encode(['stage' => ChatStage::STAGE_INIT]);
            $stageModel->save();
        }

        $this->writeInfoLogs('Stage', $stageModel);

        if (!isset($stageModel->data) || empty($stageModel->data)) {
            $this->writeErrorLogs('Stage data is empty by chat_id' . $this->chatId);
            return;
        }

        $data = json_decode($stageModel->data, true);
        if (empty($data['stage'])) {
            $this->writeErrorLogs('Stage does\'t exist by chat_id ' . $this->chatId);
            return;
        }

        if (!in_array($data['stage'], ChatStage::getAllowedStages())) {
            $this->writeErrorLogs('Invalid stage in chat stage storage ' . $data['stage']);
            return;
        }

        // set stage
        $this->stageInput = $data['stage'];

        $adminIds = $this->getAdminIdsByChatId($this->chatId);
        $this->writeInfoLogs('$adminIds=', $adminIds);

        if (count($adminIds) > 1) {
            $this->writeErrorLogs('Count of admins > 1 - this case not supported yet');
            return;
        }

        // set adminId, botName
        $this->adminId = reset($adminIds);
        $this->botName = $this->getBotName();

        if (empty($this->botName)) {
            $this->writeErrorLogs('Empty bot name');
            return;
        }

        // get msg
        $msg = $this->getMessageTextFromRequest($requestArr);

        $this->writeInfoLogs('$botName', $this->botName);

        // prepare response
        try {
            $response = $this->prepareResponseByParams($msg);
            $this->writeInfoLogs('Prepared response' ,$response);
        } catch (\Exception $e) {
            $this->writeErrorLogs($e->getMessage());

            $response = [
                'text' => 'Looks like something went wrong. Try again, please',
                'reply_markup' => $this->markupBuilder->getMarkupInit(!empty($this->walletAddress)),
            ];
            $this->stageOutput = ChatStage::STAGE_INIT;
        }

        $response['chat_id'] = $this->chatId;

        // do action
        $this->writeInfoLogs('Msg are sending');
        $this->telegram->sendMessage($response);
        $this->writeInfoLogs('Msg has been successfully sent');

        // set new stage
        $stageModel = ChatStage::find($this->chatId);
        $data = json_decode($stageModel->data, true);
        $data['stage'] = $this->stageOutput;
        $stageModel->data = json_encode($data);
        $stageModel->save();

        $this->writeInfoLogs('END');
    }

    /**
     * @return string
     */
    protected function getMsgTextAnyAnswer() {
        return 'I don\'t have answer for this case.' . PHP_EOL . 'Choose another command, please.';
    }

    /**
     * @param $msg
     * @return array
     */
    protected function prepareResponseByParams($msg) {
        $this->writeInfoLogs('Preparing response by msg:' . $msg);

        $walletExist = !empty($this->walletAddress);

        $result = [
            'text' => $this->getMsgTextAnyAnswer(),
        ];

        // Common commands
        if (empty($msg) || empty($this->stageInput) || in_array($msg, Commands::getCommandsStart())) {
            $result = [
                'text' => 'Hi, I\'m CoinDriveBot! How can I help you?' . PHP_EOL . ' If you need to stop me use /stop@' . $this->botName,
                'reply_markup' => $this->markupBuilder->getMarkupInit($walletExist),
            ];
            $this->stageOutput = ChatStage::STAGE_INIT;

        } elseif (in_array($msg, Commands::getCommandsStop())) {
            $result = [
                'text' => 'I has been stopped. For run me use /start@' . $this->botName,
                'reply_markup' => $this->markupBuilder->getMarkupEmpty(),
            ];
            $this->stageOutput = ChatStage::STAGE_INIT;
        }
        elseif (in_array($msg, Commands::getCommandsMenu())) {
            $result = [
                'text' => 'I\'m CoinDriveBot! How can I help you?' . PHP_EOL . ' If you need to stop me use /stop@' . $this->botName,
                'reply_markup' => $this->markupBuilder->getMarkupInit($walletExist),
            ];
            $this->stageOutput = ChatStage::STAGE_INIT;
        }

        // Stage Init
        elseif ($this->stageInput === ChatStage::STAGE_INIT) {
            if (in_array($msg, Commands::getCommandsCreateWallet())) {
                $result = [
                    'text' => 'Shared wallet has been created successfully.' . PHP_EOL . 'Wallet address: ' . $this->createSharedWallet(),
                    'reply_markup' => $this->markupBuilder->getMarkUpWalletCreated(),
                ];
                $this->stageOutput = ChatStage::STAGE_WALLET_CREATED;

            } elseif (in_array($msg, Commands::getCommandsUseCurrentWallet())) {
                $result = [
                    'text' => 'Current wallet address: ' . $this->walletAddress,
                    'reply_markup' => $this->markupBuilder->getMarkUpWalletCreated(),
                ];
                $this->stageOutput = ChatStage::STAGE_WALLET_CREATED;

            } elseif (in_array($msg, Commands::getCommandsAbout())) {
                $result = [
                    'text' => 'CoinDriveBot provides solution for flexible management bitcoin wallets. Add this Bot to the group and create collective wallet, so you could use this wallet together!',
                    'reply_markup' => $this->markupBuilder->getMarkupInit($walletExist),
                ];
                $this->stageOutput = ChatStage::STAGE_INIT;
            } else {
                $result = [
                    'text' => $this->getMsgTextAnyAnswer(),
                    'reply_markup' => $this->markupBuilder->getMarkupInit($walletExist),
                ];
                $this->stageOutput = ChatStage::STAGE_INIT;
            }
        }

        // Stage wallet created
        elseif ($this->stageInput === ChatStage::STAGE_WALLET_CREATED) {
            if (in_array($msg, Commands::getCommandsSend())) {
                $result = [
                    'text' => 'Enter the bitcoin wallet address, please',
                    'reply_markup' => $this->markupBuilder->getMarkupDefaultSingle(),
                ];
                $this->stageOutput = ChatStage::STAGE_MAKE_PAYMENT_ENTER_WALLET_ADDRESS;

            } elseif (in_array($msg, Commands::getCommandsWalletInfo())) {
                $result = [
                    'text' => $this->getWalletInfo(),
                    'reply_markup' => $this->markupBuilder->getMarkUpWalletCreated(),
                ];
                $this->stageOutput = ChatStage::STAGE_WALLET_CREATED;

            } elseif (in_array($msg, Commands::getCommandsAbout())) {
                $result = [
                    'text' => 'CoinDriveBot provides solution for flexible management bitcoin wallets. Add this Bot to the group and create collective wallet, so you could use this wallet together!',
                    'reply_markup' => $this->markupBuilder->getMarkUpWalletCreated(),
                ];
                $this->stageOutput = ChatStage::STAGE_WALLET_CREATED;

            } else {
                $result = [
                    'text' => $this->getMsgTextAnyAnswer(),
                    'reply_markup' => $this->markupBuilder->getMarkUpWalletCreated(),
                ];
                $this->stageOutput = ChatStage::STAGE_WALLET_CREATED;
            }
        }

        // Stage enter wallet address
        elseif ($this->stageInput === ChatStage::STAGE_MAKE_PAYMENT_ENTER_WALLET_ADDRESS) {
            if ($msg === Commands::COMMAND_TITLE_BACK) {
                $result = [
                    'text' => 'I\'m CoinDriveBot! How can I help you?' . PHP_EOL . ' If you need to stop me use /stop@' . $this->botName,
                    'reply_markup' => $this->markupBuilder->getMarkUpWalletCreated(),
                ];
                $this->stageOutput = ChatStage::STAGE_WALLET_CREATED;

            } elseif ($this->validateWalletAddress($msg)) {
                // save wallet address
                $chatStage = ChatStage::find($this->chatId);
                if (!$chatStage) {
                    $this->writeErrorLogs('Stage not found by chat_id ' . $this->chatId);
                    return;
                }

                $data = json_decode($chatStage->data, true);
                $data['wallet_address'] = trim($msg);
                $chatStage->data = json_encode($data);
                $chatStage->save();

                // prepare response
                $result = [
                    'text' => 'Enter the amount (BTC), please',
                    'reply_markup' => $this->markupBuilder->getMarkupDefault(),
                ];
                $this->stageOutput = ChatStage::STAGE_MAKE_PAYMENT_ENTER_AMOUNT;
            } else {
                $result = [
                    'text' => 'Invalid bitcon wallet address. Try again, please',
                    'reply_markup' => $this->markupBuilder->getMarkupDefault(),
                ];
                $this->stageOutput = ChatStage::STAGE_MAKE_PAYMENT_ENTER_WALLET_ADDRESS;
            }
        }

        // Stage enter amount
        elseif ($this->stageInput === ChatStage::STAGE_MAKE_PAYMENT_ENTER_AMOUNT) {
            if ($msg === Commands::COMMAND_TITLE_BACK) {
                $result = [
                    'text' => 'Enter the bitcoin wallet address, please',
                    'reply_markup' => $this->markupBuilder->getMarkupDefault(),
                ];
                $this->stageOutput = ChatStage::STAGE_MAKE_PAYMENT_ENTER_WALLET_ADDRESS;

            } else {
                $amount = $this->prepareAmount($msg);

                if ($this->validateAmount($msg)) {
                    // save amount
                    $chatStage = ChatStage::find($this->chatId);
                    if (!$chatStage) {
                        $this->writeErrorLogs('Stage not found by chat_id ' . $this->chatId);
                        return;
                    }

                    $data = json_decode($chatStage->data, true);
                    $data['amount'] = trim($amount);
                    $chatStage->data = json_encode($data);
                    $chatStage->save();

                    if (empty($data['wallet_address'])) {
                        $this->writeErrorLogs('Empty wallet address');
                        return;
                    }

                    // prepare result
                    $result = [
                        'text' => 'Do you accept sending ' . $amount . ' BTC to wallet ' . $data['wallet_address'] . ' ?',
                        'reply_markup' => $this->markupBuilder->getMarkUpConfirmPayment(),
                    ];
                    $this->stageOutput = ChatStage::STAGE_CONFIRM_PAYMENT;
                } else {
                    $result = [
                        'text' => 'Invalid amount. Try again, please',
                        'reply_markup' => $this->markupBuilder->getMarkupDefault(),
                    ];
                    $this->stageOutput = ChatStage::STAGE_MAKE_PAYMENT_ENTER_AMOUNT;
                }
            }
        }

        // Stage confirm payment
        elseif ($this->stageInput === ChatStage::STAGE_CONFIRM_PAYMENT) {
            $chatStage = ChatStage::find($this->chatId);
            if (!$chatStage) {
                $this->writeErrorLogs('Stage not found by chat_id ' . $this->chatId);
                return;
            }

            if ($msg === Commands::COMMAND_TITLE_BACK) {
                $result = [
                    'text' => 'Enter the amount, please',
                    'reply_markup' => $this->markupBuilder->getMarkupDefault(),
                ];
                $this->stageOutput = ChatStage::STAGE_MAKE_PAYMENT_ENTER_AMOUNT;

            } elseif ($msg === Commands::COMMAND_TITLE_YES) {
                $data = json_decode($chatStage->data, true);

                if (empty($data['wallet_address'])) {
                    $this->writeErrorLogs('Empty wallet address');
                    return;
                }
                if (empty($data['amount'])) {
                    $this->writeErrorLogs('Empty amount');
                    return;
                }

                // clean stage data
                $chatStage->data = json_encode(['stage' => ChatStage::STAGE_INIT]);
                $chatStage->save();

                // make payment
                $paymentResponse = BlockchainApi::sendFunds($this->chatId, $data['wallet_address'], $data['amount']);
                $this->writeInfoLogs($paymentResponse);

                $result = [
                    'text' => $paymentResponse,
                    'reply_markup' => $this->markupBuilder->getMarkupInit($walletExist),
                ];
                $this->stageOutput = ChatStage::STAGE_INIT;

            } elseif ($msg === Commands::COMMAND_TITLE_NO) {
                // clean stage data
                $chatStage->data = json_encode(['stage' => ChatStage::STAGE_INIT]);
                $chatStage->save();

                $result = [
                    'text' => 'I\'m CoinDriveBot! How can I help you?' . PHP_EOL . ' If you need to stop me use /stop@' . $this->botName,
                    'reply_markup' => $this->markupBuilder->getMarkupInit($walletExist),
                ];
                $this->stageOutput = ChatStage::STAGE_INIT;
            } else {
                // clean stage data
                $chatStage->data = json_encode(['stage' => ChatStage::STAGE_INIT]);
                $chatStage->save();

                $result = [
                    'text' => $this->getMsgTextAnyAnswer(),
                    'reply_markup' => $this->markupBuilder->getMarkUpConfirmPayment(),
                ];
                $this->stageOutput = ChatStage::STAGE_CONFIRM_PAYMENT;
            }
        } else {
            $result['reply_markup'] =  $this->markupBuilder->getMarkupInit($walletExist);
            $this->stageOutput = ChatStage::STAGE_INIT;
        }

        return $result;
    }

    /**
     * @param $msg
     * @return bool
     */
    protected function validateWalletAddress($msg) {
        preg_match('#^[A-Z0-9]+$#i', $msg, $matches);

        return !empty($matches);
    }

    /**
     * @param $msg
     * @return bool
     */
    protected function validateAmount($msg) {
        preg_match('#^[0-9]{1}\.[0-9]{1,8}$#', $msg, $matches);

        return !empty($matches) && $msg < 1 && $msg > 0;
    }

    /**
     * Return result with 8 numbers after dot rounded to up
     *
     * @param $amount
     * @return string
     */
    protected function prepareAmount($amount) {
        return number_format($amount, 8, '.', '');
    }

    /**
     * @return string
     */
    protected function createSharedWallet() {
        return BlockchainApi::createWallet($this->chatId, $this->adminId);
    }

    /**
     * @return string
     */
    protected function getWalletInfo() {
        $balance = BlockchainApi::getBalance($this->chatId);

        return 'Wallet address: ' . $this->walletAddress . PHP_EOL . 'Wallet balance: ' . $balance;
    }

    /**
     * @return string
     */
    protected function getBotName() {
        $user = $this->telegram->getMe();

        $this->writeInfoLogs('$user', json_encode($user));

        return $user->getUsername();
    }

    /**
     * @param array $request
     * @return string
     */
    protected function getMessageTextFromRequest(array $request) {
        return !empty($request['message']['text']) ? trim($request['message']['text']) : '';
    }

    /**
     * @param array $request
     * @return int
     */
    protected function getChatIdFromRequest(array $request) {
        return !empty($request['message']['chat']['id']) ? (int)$request['message']['chat']['id'] : 0;
    }

    /**
     * @param array $request
     * @return int
     */
    protected function getChatTypeFromRequest(array $request) {
        return !empty($request['message']['chat']['type']) ? $request['message']['chat']['type'] : null;
    }

    /**
     * @param int $chatId
     * @return array
     */
    protected function getAdminIdsByChatId($chatId) {
        /* Example of $response Obj
        {
            "ok":true,
            "result":[
                {
                    "user":{
                        "id":97440540,
                        "first_name":"Stanislav",
                        "last_name":"Honcharenko",
                        "username":"StasHoncharenko"
                    },
                    "status":"creator"
                }
            ]
        }
        */
        @$response = file_get_contents(TELEGRAM_API_URL . 'getChatAdministrators?chat_id=' . (int)$chatId);
        @$response = json_decode($response);

        $result = [];
        if (isset($response->ok) && $response->ok == true && isset($response->result) && is_array($response->result)) {
            foreach ($response->result as $obj) {
                if (isset($obj->user->id)) {
                    $id = (int)$obj->user->id;
                    $result[$id] = $id;
                }
            }
        }

        return $result;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function webhookActionTmp(Request $request) {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'), true);
        $telegram->commandsHandler(true);

        $this->writeLogs(json_encode($request->all()));
        define('API_URL', 'https://api.telegram.org/bot'. env('TELEGRAM_BOT_TOKEN') .'/');

        // read incoming info and grab the chatID
        $content = file_get_contents("php://input");
        $update = json_decode($content, true);
        $chatID = $update["message"]["chat"]["id"];

        $this->writeLogs('chat_id=' . $chatID);

        // compose reply
        $reply =  'Message from staging-one.tk datetime:' . date('Y-m-d H:i:s');

        // send reply
        $sendto =API_URL."sendmessage?chat_id=".$chatID."&text=".$reply;
        file_get_contents($sendto);
    }



    public function setWebhookAction() {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $url = env('APP_PROD_URL') . '/' . env('TELEGRAM_BOT_TOKEN') . '/webhook';
        $response = $telegram->setWebhook(['url' => $url]);

        die(var_dump($response));
    }

    public function testBotAction() {
        $this->prepareAmount(0);
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));

        $response = $telegram->getMe();

        $botId = $response->getId();
        $firstName = $response->getFirstName();
        $username = $response->getUsername();

        die(var_dump($botId, $firstName, $username));
    }

    public function setKeyboard() {
        $keyboard = [
            [(object)['text' => 'Create wallet']],
            ['About']
        ];

        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $reply_markup = $telegram->replyKeyboardMarkup([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => false,
        ]);

        $response = $telegram->sendMessage([
            'chat_id' => '-137820493',
            'text' => 'Hi, I\'m CoinDriveBot! How can I help you?',
            'reply_markup' => $reply_markup
        ]);

        $messageId = $response->getMessageId();
        die(var_dump($messageId));
    }

    public function removeWebhookAction() {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $response = $telegram->removeWebhook();

        die(var_dump($response));
    }

    public function getUpdatesAction() {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $response = $telegram->getUpdates();
        $response = json_encode($response, true);
        die(var_dump($response));
    }

    /**
     * @param $msg
     * @param $data
     */
    protected function writeInfoLogs($msg, $data = null) {
        $this->writeLogs(self::LOG_INFO, $msg, $data);
    }

    /**
     * @param $msg
     * @param $data
     */
    protected function writeSuccessLogs($msg, $data = null) {
        $this->writeLogs(self::LOG_SUCCESS, $msg, $data);
    }

    /**
     * @param $msg
     * @param $data
     */
    protected function writeWarningLogs($msg, $data = null) {
        $this->writeLogs(self::LOG_WARNING, $msg, $data);
    }

    /**
     * @param $msg
     * @param $data
     */
    protected function writeErrorLogs($msg, $data = null) {
        $this->writeLogs(self::LOG_ERROR, $msg, $data);
    }

    /**
     * @param $action
     * @param $msg
     * @param $data
     */
    protected function writeLogs($action, $msg, $data = null) {
        if (is_array($data) || is_object($data)) {
            $data = json_encode($data);
        }

        $msg = $action . ' | ' . date('Y-m-d H:i:s') . ' | ' . $msg;
        if ($data) {
            $msg .= ' ' . $data;
        }
        $path = public_path() . '/debug_logs.txt';
        if (!file_exists($path)) {
            fopen($path,"w");
        }

        $msg = file_get_contents($path) . PHP_EOL . $msg;
        file_put_contents($path, $msg);
    }
}
