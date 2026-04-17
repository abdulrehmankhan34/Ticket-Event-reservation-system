<?php

namespace App\Message;

final class GenerateEticketMessage
{
    public function __construct(public readonly int $bookingItemId)
    {
    }
}

