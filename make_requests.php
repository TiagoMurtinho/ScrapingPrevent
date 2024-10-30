<?php

$urls = [
    "http://localhost//ScrapingPrevent/Views/index.php",
    "http://localhost//ScrapingPrevent/Views/about.php",
    "http://localhost//ScrapingPrevent/Views/form.php",
    "http://localhost//ScrapingPrevent/Views/results.php",
];

$referer = "http://localhost/ScrapingPrevent/";
$userIdentification = '123413131231';
$responses = [];

foreach ($urls as $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "abc");
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_REFERER, $referer);
    curl_setopt($ch, CURLOPT_COOKIE, "user_identification=$userIdentification");

    $response = curl_exec($ch);

    if ($response === false) {
        $responses[$url] = 'Erro: ' . curl_error($ch);
    } else {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $responses[$url] = [
            'code' => $httpCode,
            'response' => $response,
        ];
    }

    curl_close($ch);
}

foreach ($responses as $url => $response) {
    if (is_array($response)) {
        echo "Resposta de $url (HTTP Code: {$response['code']}):\n{$response['response']}\n\n";
    } else {
        echo "Resposta de $url:\n$response\n\n";
    }
}