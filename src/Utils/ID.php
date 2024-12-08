<?php

namespace JVVM\Utils;
use Exception;

class ID {
    use \idz;
    
    protected int $id;

    function __construct(int|string $id) {
        if (!self::check($id)) {
            throw new Exception('Invalid ID');
        }
        if (is_int($id)) {
            $this->id = $id;
        } else {
            $this->id = self::toint($id);
        }
    }

    public static function create () {
        return new ID(self::generate(10));
    }

    function get():int {
        return $this->id;
    }

    function __toString():string {
        return $this->format(self::toref($this->id));
    }
}