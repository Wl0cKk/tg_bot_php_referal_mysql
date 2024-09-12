<?php

include 'class/Telegram.php';
include 'class/DatabaseConnection.php';
include 'src/handlers.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$config_file = require __DIR__ . '/config.php';

if (!isset($config_file['db'])) { die("# db key error - Database configuration not found."); }

$bot_token = $config_file['bot_token'];
$telegram = new Telegram($bot_token);
$GLOBALS['TOKEN'] = $bot_token;
$text = $telegram->Text();
$chat_id = $telegram->ChatID();
$data = $telegram->getData();

$GLOBALS['config'] = [
    'adminHREF' => 'https://t.me/t1nkov',
    'summ' => 500,
    'inviteSumValue' => 200,
    'offTgChannel' => 'https://t.me/fgjhaksdlf',
    'cards' => 10,
    'ChannelID' => 2248476665,
    'subscribeSumValue' => '1000 INR',
    'watchSumValue' => 8,
    'valueTg' => 1,
    'currency' => 'INR',
    'joinChannelPay' => '1000 INR',
    'minWithdraw' => number_format(10, 2, '.', '') . ' INR',
    'bonus' => '10 INR',
    'buttons' => [
        "ru" => ["Русский"],
        "en" => ["English"],
        "kz" => ["Қазақша"]
    ]
];

$update = json_decode(file_get_contents('php://input'), true);
$callback_data = $update['callback_query']['data'] ?? null;
$message_id = $update['callback_query']['message']['message_id'] ?? null;
$GLOBALS['username1'] = $data['message']['from']['username'] ?? null;

$db = new DatabaseConnection($config_file);

handleCallbackData($callback_data, $telegram, $chat_id, $message_id, $db);

handleTextCommands($text, $telegram, $chat_id, $update, $db);
