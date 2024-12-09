<?php

namespace JVVM\Utils;
class RouterMethod {
    static function redirect():int {
        return 0x0200;
    }
    static function is_router_method($method):bool {
        return $method & 0xFE00;
    }
}
