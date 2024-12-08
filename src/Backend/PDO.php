<?php

namespace JVVM\Backend;

use PDO as PDOBase;

class PDO extends PDOBase {
    static PDO|null $instance = null;

    function __construct($dsn, $username = null, $password = null, $options = null) {
        if (self::$instance !== null) {
            return self::$instance;
        }
        parent::__construct($dsn, $username, $password, $options);
        $this->setAttribute(PDOBase::ATTR_ERRMODE, PDOBase::ERRMODE_EXCEPTION);
        self::$instance = $this;
    }

    static function getInstance() {
        return self::$instance;
    }
}