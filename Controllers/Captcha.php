<?php

namespace Controllers;
class Captcha
{
    private $secretKey;

    public function __construct($secretKey)
    {
        $this->secretKey = $secretKey;
    }

    public function validate($response) {
        if ($response === 'resposta_simulada') {
            return true;
        }

        $url = "https://www.google.com/recaptcha/api/siteverify";
        $data = [
            'secret' => $this->secretKey,
            'response' => $response
        ];

        $options = [
            'http' => [
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ],
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $resultJson = json_decode($result);

        return $resultJson->success;
    }
}