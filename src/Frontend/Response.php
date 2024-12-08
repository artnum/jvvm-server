<?php

namespace JVVM\Frontend;

use Exception;
use UUID;

class Response {
    static ?Response $instance = null;
    protected int $length;
    protected int $errors;
    protected string $id;
    protected bool $opened;
    protected bool $closed;

    function __construct() {
        if (self::$instance !== null) {
            return self::$instance;
        }
        if (empty(ob_get_status())) {
            ob_start();
        }
        $this->id = uniqid();
        $this->length = 0;
        $this->errors = 0;
        $this->opened = false;
        $this->closed = false;
        self::$instance = $this;
    }

    function header(string $header, bool $replace) {
        if ($this->opened) { return; }
        header($header, $replace);
    }

    function error(string|Exception $e) {
        if (!$this->opened) {
            error_log('Writing data on closed channel');
            return;
        }
        $this->errors++;
        $this->length++;
        echo json_encode([
            '__set' => 'error',
            'message' => is_string($e) ? $e : $e->getMessage(),
            'code' => is_string($e) ? 0 : $e->getCode()
        ]) . ',';
    }

    function open() {
        if ($this->opened) {
            return;
        }
        header('Content-Type: application/json;charset=utf-8', true, 200);
        ob_end_flush();
        printf('[{"__set":"start","id":"%s"},', $this->id);
        $this->opened = true;
    }

    function emit(mixed $object) {
        if (!$this->opened) {
            error_log('Writing data on closed channel');
            return;
        }
        if (empty($object)) { return; }
        $str = json_encode(['__set' => 'item', 'item' => $object]);
        if (is_null($str)) { return; }
        echo $str . ',';
        $this->length++;
    }

    function close() {
        if ($this->closed) {
            return;
        }
        printf(
            '{"__set":"end","id":"%s","length":%d,"errors":%d}]',
            $this->id,
            $this->length,
            $this->errors
        );
        $this->closed = true;
    }
}