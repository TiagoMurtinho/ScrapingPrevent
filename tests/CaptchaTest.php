<?php

use Controllers\Captcha;
use PHPUnit\Framework\TestCase;

require_once 'Captcha.php';

class CaptchaTest extends TestCase
{
    public function testValidate()
    {
        $captcha = new Captcha('6Lc5FWMqAAAAANTojmQ8gMgaTx5qijIFd-_7eRnx');

        $testResponse = 'resposta_simulada';

        $this->assertTrue($captcha->validate($testResponse));
    }
}