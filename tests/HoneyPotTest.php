<?php

use Controllers\HoneyPot;
use PHPUnit\Framework\TestCase;

require_once 'HoneyPot.php';

class HoneyPotTest extends TestCase
{
    public function testValidate()
    {
        $honeyPot = new HoneyPot();

        $this->assertTrue($honeyPot->validate(['campo_honeypot' => 'valor_vazio']));
    }
}