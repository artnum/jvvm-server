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

    function __construct(string $uri, string $delimiter = '$api') {
        $parts = explode('?', $uri, 2);
        if (isset($parts[1])) {
            $this->parse_query_string($parts[1]);
        }
        $parts = explode('/', $parts[0]);
        while(array_shift($parts) !== $delimiter);
        $parts = array_map(
            fn($e) => urldecode($e),
            array_filter(
                $parts,
                fn($e) => !empty($e)
            )
        );
        $this->parts = $parts;
        $this->position = 0;
        $this->method = HTTPMethod::detect();
        $this->id = '';
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
}