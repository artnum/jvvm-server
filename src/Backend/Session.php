<?php

namespace JVVM\Backend;

use JVVM\Backend\PDO;
use JVVM\Utils\ID;
use JVVM\Utils\SQL;

class Session {
    protected ID|false $current;
    protected PDO $pdo;
    static Session|null $instance = null;

    function __construct() {
        if (!is_null(self::$instance)) {
            return self::$instance;
        }
        $this->pdo = PDO::getInstance();
        $this->current = false;
        $this->init();
        self::$instance = $this;
    }

    function init() {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            if (preg_match(
                '/Bearer\s+([A-Z0-9\-]+).*/', 
                $_SERVER['HTTP_AUTHORIZATION'],
                $matches
            )) {
                $id = new ID($matches[1]);
                return $this->check($id);
            }
        }

        if (isset($_COOKIE['AUTHORIZATION'])) {
            $id = new ID($_COOKIE['AUTHORIZATION']);
            return $this->check($id);
        }
    }

    static function getInstance() {
        if (is_null(self::$instance)) {
            return new Session();
        }
        return self::$instance;
    }

    function create(ID $member_id):ID {
        error_log('CREATE');
        SQL::lock_table($this->pdo, 'member_session');
        $id = SQL::create_id($this->pdo, 'member_session', 'session_id');
        $stmt = $this->pdo->prepare(
            'INSERT INTO member_session (session_id, member_id, since) VALUES
            (:session_id, :member_id, :since);'
        );
        $stmt->bindValue(':session_id', $id->get(), PDO::PARAM_INT);
        $stmt->bindValue(':member_id', $member_id->get(), PDO::PARAM_INT);
        $stmt->bindValue(':since', time(), PDO::PARAM_INT);
        $stmt->execute();
        SQL::unlock_table($this->pdo);
        $this->current = $id;
        return $this->current;
    }

    function delete(ID $session_id) {
        $stmt = $this->pdo->prepare('DELETE FROM member_session 
            WHERE session_id = :id');
        $stmt->bindValue(':id', $session_id->get(), PDO::PARAM_INT);
        $stmt->execute();
    }

    function check(ID $session_id):ID|false {
        $stmt = $this->pdo->prepare('SELECT member_id FROM member_session 
            WHERE session_id = :id');
        $stmt->bindValue(':id', $session_id->get(), PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() !== 1) { return false; }
        $row = $stmt->fetch();
        if ($row === false) { return false; }
        $this->current = new ID($row['member_id']);
        return $this->current;
    }

    function has_session():ID|false {
        return $this->current;
    }

}