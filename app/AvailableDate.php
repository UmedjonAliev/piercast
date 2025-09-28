<?php

declare(strict_types=1);

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;

class AvailableDate
{
    public function perform(): void
    {
        $dates = [
            'year=2025&month=10',
            'year=2025&month=11',
        ];

        $cookieFile = __DIR__ . '/cookies.txt';
        $domain = 'pieraksts.mfa.gov.lv';

        $cookieJar = loadCookies($cookieFile, $domain);
        $sessionId = getCookieValue($cookieJar, 'mfaSchedulerSession');
        $cookCsrf = getCookieValue($cookieJar, '_csrf-mfa-scheduler');

        // 2. Create client with cookies
        $cookieHeader = "mfaSchedulerSession={$sessionId}; _csrf-mfa-scheduler={$cookCsrf}";

        $client = new Client([
            'base_uri' => 'https://pieraksts.mfa.gov.lv',
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Cookie' => $cookieHeader,
            ]
        ]);

        foreach ($dates as $date) {
            $url = "/ru/calendar/available-month-dates?$date";

            retry:

            try {
                $response = $client->get($url);
            } catch (ServerException|ConnectException $e) {
                setErrorLog('AvailableDate', $e->getMessage());
                sleep(1);
                goto retry;
            }
            $result = $response->getBody()->getContents();

            $decode = json_decode($result);

            if ($decode != "Šobrīd visi pieejamie laiki ir aizņemti") {
                $msg = "✅ НАЙДЕНЫ ДОСТУПНЫЕ ДАТЫ! \n
                    URL: $url \n
                    Ответ: " . json_encode($result);

                (new TelegramSender())->perform($msg);

                // переход на дату
                if ($response->getStatusCode() < 400) {
                    foreach ($decode as $value) {
                        $date = '/ru/calendar/available-time-slots?date=' . $value;
                        $response = $client->get($date);
                        $times = $response->getBody()->getContents();

                        (new TelegramSender())->perform('дата: /ru/calendar/available-time-slots?date=' . $value);
                        (new TelegramSender())->perform(json_encode($times));
                    }
                }
            }
        }
    }
}