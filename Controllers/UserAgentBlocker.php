<?php

namespace Controllers;
class UserAgentBlocker
{
    private $blockedAgents;

    public function __construct($blockedAgents = [])
    {
        $this->blockedAgents = $blockedAgents;
    }

    public function isAllowed($userAgent): bool
    {

        if (empty($userAgent)) {
            return false;
        }

        foreach ($this->blockedAgents as $blockedAgent) {
            if (stripos($userAgent, $blockedAgent) !== false) {
                return false;
            }
        }
        return true;
    }
}