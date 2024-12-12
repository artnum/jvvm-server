<?php

namespace JVVM\Utils;

use Iterator;
use JVVM\Utils\HTTPMethod;

class URI implements Iterator {  
    protected array $parts = [];
    protected array $qs = [];
    protected int $position;
    protected int $method;
    protected string $id;
    protected bool $real; /* a real URI is one that came from the server, if 
                           * build in app, it's not a real one, it's a possible
                           * one.
                           */

    function __construct(string $uri) {
        $parts = explode('?', $uri, 2);
        if (isset($parts[1])) {
            $this->parse_query_string($parts[1]);
        }
        $parts = explode('/', $parts[0]);
        $parts = array_map(
            fn($e) => urldecode($e),
            array_filter(
                $parts,
                fn($e) => !empty($e)
            )
        );
        $this->parts = array_values($parts);
        $this->position = 0;
        $this->id = '';
        $this->method = -1;
        $this->real = false;
    }

    static function fromRequest(string $uri) {        
        $parts = preg_split('/\$[a-z]+/', $uri);
        $uri = new URI(array_pop($parts));
        $uri->method = HTTPMethod::detect();
        $uri->real = true;
        return $uri;
    }

    function get_base():string {
        if (!isset($this->parts[0])) { return ''; }
        return $this->parts[0];
    }

    function is_real():bool {
        return $this->real;
    }

    function copy_from(URI $uri, ?URI $template = null) {
        $this->method = $uri->get_method();
        $this->real = $uri->is_real();
        $this->qs =$uri->get_query_string();
        if ($template != NULL) {
            $copy = false;
            $i = 0;
            $original = $uri->get_uri_components();
            $tpl = $template->get_uri_components();
            for ($i = 0; $i < count($original); $i++) {
                if (!$copy 
                    && isset($tpl[$i])
                    && $tpl[$i] === '{*}'
                ) {
                    $copy = true;
                }
                if ($copy) {
                    $this->parts[$i] = $original[$i];
                }
            }
            
        }
    }

    function set_qs_variable(string $name, mixed $value) {
        $this->qs[$name] = $value;
    }

    private function parse_query_string(string $qs) {
        $pairs = explode('&', $qs);
        foreach($pairs as $pair) {
            $kv = explode('=', $pair, 2);
            if (!isset($kv[1])) { $kv[1] = ''; }
            if (str_ends_with($kv[0], '[]')) {
                $kv[0] = urldecode(substr($kv[0], 0, -2));
                if (!isset($this->qs[$kv[0]])) {
                    $this->qs[$kv[0]] = [];
                }
                $this->qs[$kv[0]][] = urldecode($kv[1]);
                continue;
            }
            $this->qs[urldecode($kv[0])] = urldecode($kv[1]);
        }
    }

    function get_id(int $method = -1):string {
        if ($this->id !== '') { return $this->id; }
        $ctx = hash_init('xxh3');
        hash_update($ctx, $method === -1 ? $this->method : $method);
        foreach($this->parts as $part) {
        hash_update($ctx, $part);
        }
        $this->id = hash_final($ctx);
        return $this->id;
    }

    function get_uri_components():array {
        return $this->parts;
    }

    function get_method():int {
        return $this->method;
    }

    function get_query_string():array {
        return $this->qs;
    }    

    public function current():mixed {
        return $this->parts[$this->position];
    }

    public function key():mixed {
        return $this->position;
    }

    public function next():void {
        $this->position++;
    }

    public function rewind():void {
        $this->position = 0;
    }

    public function valid():bool {
        return $this->position < count($this->parts);
    }

    function dump() {
        var_dump($this->parts);
    }

    public function __toString():string {
        return join('/', $this->parts);
    }
}