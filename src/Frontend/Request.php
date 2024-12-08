<?php

namespace JVVM\Frontend;

use JVVM\Utils\URI;
use stdClass;

class Request {
    protected URI $uri;
    protected bool $read;

    function __construct (URI $uri) {
        $this->uri = $uri;
        $this->read = false;
    }
    function get_body():string {
        if ($this->read) { return ''; }
        $this->read = true;
        return file_get_contents('php://input');
    }

    function get_json_body():stdClass|array {
        if ($this->read) {
            return new stdClass();
        }
        $body = json_decode($this->get_body());
        if (is_null($body)) { return new stdClass(); }
        return $body;
    }

    function get_uri():URI {
        return $this->uri;
    }
}