<?php

use GuzzleHttp\Cookie\CookieJar;

function getCookieValue(CookieJar $jar, string $name): ?string
{
    foreach ($jar->toArray() as $cookie) {
        if ($cookie['Name'] === $name) {
            return $cookie['Value'];
        }
    }
    return null; // если куки с таким именем нет
}

// Функции для работы с куки (из предыдущего примера)
function loadCookies(string $file, string $domain): CookieJar
{
    $cookies = [];
    if (file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            [$name, $value, $expires] = explode('|', $line);
            if (time() < (int)$expires) {
                $cookies[$name] = $value;
            }
        }
    }
    return CookieJar::fromArray($cookies, $domain);
}

function saveCookies(CookieJar $jar, string $file): void
{
    $lines = [];
    foreach ($jar->toArray() as $cookie) {
        $lines[] = $cookie['Name'] . '|' . $cookie['Value'] . '|' . (time() + 1200);
    }
    file_put_contents($file, implode(PHP_EOL, $lines));
}

function isActiveSession($file): bool
{
    if (file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            [$name, $value, $expires] = explode('|', $line);
            if (time() < $expires) return true;
        }
    }
    return false;
}

function getProjectDir()
{
    return realpath(__DIR__);
}

function setErrorLog($class,$message)
{
    $logMessage = "[" . date('Y-m-d H:i:s') . "] SERVER ERROR $class: " . $message . PHP_EOL;
    error_log($logMessage, 3, getProjectDir() . '/logs/error.log');
}