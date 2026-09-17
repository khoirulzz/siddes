<?php

namespace App\Services\Messaging;

use RuntimeException;

class MessagingException extends RuntimeException
{
    public function __construct(string $message, public readonly int $httpStatus = 503, public readonly bool $uncertain = false)
    {
        parent::__construct($message);
    }
}
