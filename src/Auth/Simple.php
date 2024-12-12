<?php

namespace JVVM\Auth;

use PDO;
use JVVM\Utils\ID;
use JVVM\Backend\Session;

class Simple implements IAuth {
    protected PDO $pdo;
    const TYPE = 'simple';

    function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    function login (
        string $identifier,
        string $parameter1 = '',
        string $parameter2 = '',
        string $parameter3 = '',
        string $parameter4 = ''
    ):ID {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM member_auth WHERE identifier = :identifier AND type = :type'
        );
        $stmt->bindValue(':identifier', $identifier, PDO::PARAM_STR);
        $stmt->bindValue(':type', self::TYPE, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();
        if (!$row) { return false; }
        if (!password_verify($parameter1, $row['parameter1'])) { return false; }

        $session = Session::getInstance();
        return $session->create(new ID($row['member_id']));
    }

    function update (
        ID $member_id,
        string $identifier,
        string $parameter1 = '',
        string $parameter2 = '',
        string $parameter3 = '',
        string $parameter4 = ''
    ):ID {
        $password = password_hash($parameter1, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare(
            'INSERT INTO member_auth 
                (member_id, identifier, parameter1, type, _created, _modified) 
             VALUES
                (:member_id, :identifier, :password, :type, :created, :created)
             ON DUPLICATE KEY UPDATE parameter1 = :password, _modified = :created'
        );
        $stmt->bindValue(':member_id', $member_id->get(), PDO::PARAM_INT);
        $stmt->bindValue(':created', time(), PDO::PARAM_INT);
        $stmt->bindValue(':identifier', $identifier, PDO::PARAM_STR);
        $stmt->bindValue(':password', $password, PDO::PARAM_STR);
        $stmt->bindValue(':type', self::TYPE, PDO::PARAM_STR);
        $stmt->execute();
        return $member_id;
    }
}