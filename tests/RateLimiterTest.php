<?php

use Controllers\RateLimiter;
use PHPUnit\Framework\TestCase;

require_once 'RateLimiter.php';

class RateLimiterTest extends TestCase
{
    public function testIsAllowed()
    {
        $rateLimiter = new RateLimiter(100, 3600);

        for ($i = 0; $i < 100; $i++) {
            $this->assertTrue($rateLimiter->isAllowed());
            $this->assertTrue($result, "Requisição $i deveria ser permitida, mas foi negada.");
        }

        $this->assertFalse($rateLimiter->isAllowed());
    }

    public function testRateLimitingResets()
    {
        $rateLimiter = new RateLimiter(100, 1);

        for ($i = 0; $i < 100; $i++) {
            $result = $rateLimiter->isAllowed();
            $this->assertTrue($result, "Requisição $i deveria ser permitida, mas foi negada.");
        }

        $this->assertFalse($rateLimiter->isAllowed(), "A 101ª requisição deveria ser negada.");

        sleep(1);

        $result = $rateLimiter->isAllowed();
        $this->assertTrue($result, "Requisição após reset deveria ser permitida, mas foi negada.");
    }
}