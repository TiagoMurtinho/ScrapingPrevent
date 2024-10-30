<?php

use Controllers\UserAgentBlocker;
use PHPUnit\Framework\TestCase;

require_once 'UserAgentBlocker.php';

class UserAgentBlockerTest extends TestCase
{
    public function testIsAllowed()
    {
        $blocker = new UserAgentBlocker(['curl', 'scrapy', 'python']);

        $this->assertTrue($blocker->isAllowed('Mozilla/5.0'));

        $this->assertFalse($blocker->isAllowed('curl/7.58.0'));
    }
}