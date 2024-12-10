<?php

namespace JVVM\Utils\Exceptions;

use Exception;

class SQLLock extends Exception {
    function __construct(
            string $message = 'SQL Lock error',
            ?Exception $e = null
    ) {
        error_log($message);
        parent::__construct($message, 500, $e);
    }
}