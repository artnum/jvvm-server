<?php
namespace JVVM\Backend\Content;

use JVVM\Backend\PDO;
use JVVM\Utils\ID;
use Exception;
use JVVM\Utils\Exceptions\AppError;
use JVVM\Utils\SQL;

class Member {
    protected PDO $pdo;

    function __construct() {
        $this->pdo = PDO::getInstance();
    }

    function search (array $filters) {
        try {
            $stmt = SQL::prepare_search(
                $this->pdo,
                'member',
                $filters,
                ['id', 'lastname', 'firstname', 'status']
            );
            if (!$stmt->execute()) {
                throw new AppError('Member search failed');
            }
            while(($row = $stmt->fetch())) {
                $id = new ID($row['id']);
                yield [
                    'id' => strval($id),
                    'lastname' => $row['lastname'],
                    'firstname' => $row['firstname'],
                    'status' => $row['status']
                ];
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            throw new AppError('Member search failed', $e);
        }
    }

    function patch (ID $id, array $fields):bool {
        $fields['_modified'] = time();
        list ($update, $bindings) = SQL::fields_to_update($fields);
        $stmt = $this->pdo->prepare('UPDATE member SET ' . $update . '
            WHERE id = :id');
        foreach($bindings as $binding) {
            $stmt->bindValue(':' . $binding[0], $binding[1], $binding[2]);
        }
        $stmt->bindValue(':id', $id->get(), PDO::PARAM_INT);
        return $stmt->execute();
    }

    function replace (ID $id, string $firstname, string $lastname):bool {
        $stmt = $this->pdo->prepare(
            'UPDATE member 
            SET firstname = :firstname, lastname = :lastname, _modified = :mod
            WHERE id = :id'
        );
        $stmt->bindValue(':id', $id->get(), PDO::PARAM_INT);
        $stmt->bindValue(':firstname', $firstname, PDO::PARAM_STR);
        $stmt->bindValue(':lastname', $lastname, PDO::PARAM_STR);
        $stmt->bindValue(':mod', time(), PDO::PARAM_INT);
        return $stmt->execute();
    }

    function create (
            string $firstname,
            string $lastname,
            string $status = 'active'
    ):ID {
        $limit = 0;
        do {
            if ($limit > 10) { throw new Exception('Cannot create ID, too many collision'); }
            $id = ID::create();
            $stmt = $this->pdo->query('SELECT id FROM member WHERE id = ' . $id->get());
            $limit++;
        } while ($stmt->rowCount() > 0);

        $stmt = $this->pdo->prepare(
            'INSERT INTO member
                (id, firstname, lastname, status, _modified, _created) 
             VALUES 
                (:id, :firstname, :lastname, :status, :mod, :create)'
        );
        $stmt->bindValue(':id', $id->get(), PDO::PARAM_INT);
        $stmt->bindValue(':firstname', $firstname, PDO::PARAM_STR);
        $stmt->bindValue(':lastname', $lastname, PDO::PARAM_STR);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':mod', time(), PDO::PARAM_INT);
        $stmt->bindValue(':create', time(), PDO::PARAM_INT);
        $stmt->execute();
        return $id;
    }

    function get (ID $id) {
        $result = $this->search(['id' => $id->get()]);
        if ($result->valid()) {
            return $result->current();
        }
        throw new Exception('Not found', 404);
    }
}