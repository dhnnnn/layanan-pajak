<?php

namespace App\Exceptions;

use Exception;

class ScraperUnavailableException extends Exception
{
    public function __construct(
        string $message = 'Layanan scraper tidak tersedia. Hubungi administrator.',
        int $code = 503,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
