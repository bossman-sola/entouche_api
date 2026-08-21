<?php

namespace App\Exceptions;

class StockCountValidationException extends \RuntimeException
{
    public function __construct(string $message, public readonly array $progress)
    {
        parent::__construct($message);
    }
}
