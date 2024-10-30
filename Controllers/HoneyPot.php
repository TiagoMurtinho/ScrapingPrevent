<?php

namespace Controllers;
class HoneyPot
{
    private $fieldName;

    public function __construct($fieldName = 'email')
    {
        $this->fieldName = $fieldName;
    }

    public function generateHoneyPot(): string
    {
        return "<input type='text' name='$this->fieldName' style='display:none;' />";
    }

    public function validate($postData): bool
    {
        return empty($postData[$this->fieldName]);
    }

    public function validateCookie(): bool
    {
        return isset($_COOKIE['user_identification']);
    }
}