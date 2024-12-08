<?php

namespace JVVM\Utils;

use PDO;

class SQL {
    static private function qs_op_to_sql_op ($op) {
        switch($op) {
            default:
            case 'eq': return '=';
            case 'ne': return '<>';
            case 'lt': return '<';
            case 'gt': return '>';
            case 'le': return '<=';
            case 'ge': return '>=';
            case 'like': return 'LIKE';
        }
    }
    static private function php_type_to_pdo_type (&$value) {
        if (is_int($value)) { return PDO::PARAM_INT; }
        if (is_bool($value)) { return PDO::PARAM_BOOL; }
        $value = strval($value);
        return PDO::PARAM_STR;
    }
    static function qs_to_where(array $qs, array $allowed_fields = []) {
        $where = '';
        $bindings = [];
        foreach($qs as $field => $value) {
            /* only lower case */
            $field = strtolower($field);
            if (preg_match('/[a-z][0-9a-z_]+/', $field) !== 1) {
                continue;
            }
            /* skip field not allowed */
            if (!empty($allowed_fields) && !in_array($field, $allowed_fields)) {
                continue;
            }
            $op = '=';
            $join_type = 'AND';
            $parts = explode(':', $value, 2);
            if (isset($parts[1])) {
                if ($parts[0][0] === '|') {
                    $join_type = 'OR';
                    $parts[0] = substr($parts[1], 1);
                }
                $op = SQL::qs_op_to_sql_op($parts[0]);
                $value = $parts[1];
            }
            $type = SQL::php_type_to_pdo_type($value);
            if ($type === PDO::PARAM_STR) {
                for($i = 0; $i < strlen($value); $i++) {
                    if ($value[$i] === '*') { $value[$i] = '%'; }
                }
            }
            $bindings[$field] = [$value, $type];
            $clause = sprintf('%s %s :%s', $field, $op, $field);
            if ($where === '') { 
                $where = $clause;
                continue; 
            }
            $where = sprintf("%s %s %s", $where, $join_type, $clause);
        }
        return [$where, $bindings];
    }

    static function fields_to_update(array $fields) {
        $update = '';
        $bindings = [];
        foreach($fields as $name => $value) {
            $type = self::php_type_to_pdo_type($value);
            $bindings[] = [$name, $value, $type];
            $clause = sprintf('%s = :%s', $name, $name);
            if ($update === '') { 
                $update = $clause;
                continue; 
            }
            $update = sprintf("%s, %s", $update, $clause);
        }
        return [$update, $bindings];
    }

    static function prepare_search(
            PDO $pdo,
            string $table,
            array $filters,
            array $attributes = ['*']
    ) {
        list ($where, $bindings) = SQL::qs_to_where($filters);
        $stmt_str = 'SELECT ';
        $attrs = '';
        foreach ($attributes as $attribute) {
            if ($attrs !== '') { $attrs .= ', '; }
            $attrs .= $attribute;
        }
        $stmt_str = sprintf('SELECT %s FROM %s ', $attrs, $table);
        if (!empty($where)) {
            $stmt_str .= ' WHERE ' . $where;
        }
        if (isset($filters['_count']) && ctype_digit($filters['_count'])) {
            $stmt_str .= sprintf(' LIMIT %d ', $filters['_count']);
        }
        if (isset($filters['_from']) && ctype_digit($filters['_from'])) {
            $stmt_str .= sprintf(' OFFSET %d ', $filters['_from']);
        }

        $stmt = $pdo->prepare($stmt_str);
        foreach ($bindings as $key => $binding) {
            $stmt->bindValue(':' . $key, $binding[0], $binding[1]);
        }
        return $stmt;
    }
}