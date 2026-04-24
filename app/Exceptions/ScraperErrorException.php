<?php

namespace App\Exceptions;

use Exception;

class ScraperErrorException extends Exception
{
    public function __construct(
        string $message = 'Gagal mengambil data dari Google Maps. Pastikan layanan scraper aktif.',
        int $code = 500,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
