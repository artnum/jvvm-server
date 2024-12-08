<?php

namespace JVVM\Utils\Exceptions;

use Exception;

class BadURI extends Exception {
    function __construct(?Exception $e = null) {
        parent::__construct('Bad URI', 400, $e);
    }
}