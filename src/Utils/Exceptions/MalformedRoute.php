<?php

namespace JVVM\Utils\Exceptions;

use Exception;

class MalformedRoute extends Exception {
    function __construct(?Exception $e = null) {
        parent::__construct('Malformed route', 500, $e);
    }
}