<?php

namespace JVVM\Utils;
class HTTPMethod {
    static function get():int {
        return 0x0001;
    }
    static function post():int {
        return 0x0002;
    }
    static function put():int {
        return 0x0004;
    }
    static function delete():int {
        return 0x0008;
    }
    static function patch():int {
        return 0x0010;
    }
    static function head():int {
        return 0x0020;
    }
    static function connect():int {
        return 0x0040;
    }
    static function options():int {
        return 0x0080;
    }
    static function trace():int {
        return 0x0100;
    }
    static function detect():int {
        return match(strtolower($_SERVER['REQUEST_METHOD'])) {
            'get' =>        HTTPMethod::get(),
            'post' =>       HTTPMethod::post(),
            'put' =>        HTTPMethod::put(),
            'options' =>    HTTPMethod::options(),
            'patch' =>      HTTPMethod::patch(),
            'head' =>       HTTPMethod::head(),
            'delete' =>     HTTPMethod::delete(),
            'connect' =>    HTTPMethod::connect(),
            'trace' =>      HTTPMethod::trace(),
        };
    }
}
