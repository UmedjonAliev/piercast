<?php

require 'vendor/autoload.php';

// читаем построчно .env
$lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
    if (strpos($line, '=') !== false) {
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // кладём в $_ENV и getenv()
        $_ENV[$name] = $value;
        putenv("$name=$value");
    }
}

use App\AuthCookies;
use App\AvailableDate;
use App\TelegramSender;

try {
    if (isActiveSession(__DIR__ . '/app/cookies.txt')) {
        (new AvailableDate())->perform();
        return;
    }
    (new AuthCookies())->perform();
    (new AvailableDate())->perform();

} catch (Exception $e) {
    (new TelegramSender())->perform(json_encode($e->getMessage()));
    error_log($e->getMessage(), 3, __DIR__ . '/logs/error.log');
}