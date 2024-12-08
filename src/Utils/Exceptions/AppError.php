<?php

namespace JVVM\Utils\Exceptions;

use Exception;

class AppError extends Exception {
    function __construct(
            string $message = 'General application error',
            ?Exception $e = null
    ) {
        error_log($message);
        parent::__construct($message, 500, $e);
    }
}