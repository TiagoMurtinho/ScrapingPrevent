<?php

namespace Controllers;
class RefererChecker
{
    private $allowedReferers;

    public function __construct(array $allowedReferers)
    {
        $this->allowedReferers = $allowedReferers;
    }

    public function isAllowed(): bool
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        foreach ($this->allowedReferers as $allowed) {
            if (stripos($referer, $allowed) === 0) {
                return true;
            }
        }
        return false;
    }
}