<?php

declare(strict_types=1);

namespace App;

class TelegramSender
{
    public function perform($message)
    {
        $chatId = $_ENV['TelegramChatId'];
        $token = $_ENV['TelegramBotToken'];

        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        $postFields = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $postFields,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}