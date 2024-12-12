<?php

namespace JVVM\Auth;

use JVVM\Backend\PDO;
use JVVM\Utils\Exceptions\BadURI;

class Auth {
    static function getIntance(string $auth_type):IAuth {
        switch($auth_type) {
            case 'simple':
            default: return new Simple(PDO::getInstance());
        }
        throw new BadURI();
    }
}