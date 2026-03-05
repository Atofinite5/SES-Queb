<?php

namespace BhargavKalambhe\SESQuebSDK;

use Exception;

class SESQuebException extends Exception
{
    private ?array $errors = null;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?array $errors = null,
        ?Exception $previous = null
    ) {
        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get validation errors if any
     */
    public function getErrors(): ?array
    {
        return $this->errors;
    }

    /**
     * Check if exception has validation errors
     */
    public function hasErrors(): bool
    {
        return !is_null($this->errors);
    }
}
