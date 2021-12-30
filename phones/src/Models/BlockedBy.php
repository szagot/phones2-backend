<?php

namespace App\Models;

class BlockedBy
{
    public $userMail;
    public $contactId;
    public $dateTime;

    public function __construct(string $userMail, int $contactId, string $dateTime)
    {
        $this->userMail = $userMail;
        $this->contactId = $contactId;
        $this->dateTime = $dateTime;
    }
}
