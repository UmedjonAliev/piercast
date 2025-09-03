<?php

declare(strict_types=1);

namespace App;

use GuzzleHttp\Client;

class AuthCookies
{
    public function perform(): void
    {
        $this->send();
    }


    private function send()
    {
        $cookieFile = __DIR__ . '/cookies.txt';
        $domain = 'pieraksts.mfa.gov.lv';

        $cookieJar = loadCookies($cookieFile, $domain);

        $client = new Client([
            'base_uri' => "https://$domain",
            'cookies' => true,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            ]
        ]);
        // куки делаем GET на index
        $response = $client->get('/ru/uzbekistan/index', ['cookies' => $cookieJar]);
        saveCookies($cookieJar, $cookieFile);

        // Получаем тело ответа
        $html = (string)$response->getBody();

        // === Получаем CSRF-токен из HTML ===
        preg_match('/<input[^>]+name="_csrf-mfa-scheduler"[^>]+value="([^"]+)"/', $html, $matches);
        $csrfToken = $matches[1];


        $sessionId = getCookieValue($cookieJar, 'mfaSchedulerSession');
        $cookCsrf = getCookieValue($cookieJar, '_csrf-mfa-scheduler');

        // Step 1: POST data
        $postData = [
            'branch_office_id' => 40,
            '_csrf-mfa-scheduler' => $csrfToken,
            'Persons[0][first_name]' => 'test',
            'Persons[0][last_name]' => 'test',
            'e_mail' => 'tester1717s@gmail.com',
            'e_mail_repeat' => 'tester1717s@gmail.com',
            'phone' => '+992987292937',
        ];

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

        $client->post('/ru/uzbekistan/index', [
            'form_params' => $postData,
            'allow_redirects' => false,
        ]);


        $response = $client->get('/ru/uzbekistan/step2');

        $html = (string)$response->getBody();

        preg_match('/<input[^>]+name="_csrf-mfa-scheduler"[^>]+value="([^"]+)"/', $html, $matches);
        $csrfToken2 = $matches[1];

        $client->post('ru/uzbekistan/step2', [
            'form_params' => [
                '_csrf-mfa-scheduler' => $csrfToken2,
                'Persons[0][service_ids][]' => 230
            ],
            'allow_redirects' => false,
        ]);

        $client->get('/ru/uzbekistan/step3');
    }
}