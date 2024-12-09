<?php

namespace JVVM\Frontend\Content;

use JVVM\Backend\Content\Member as BMember;
use Exception;
use JVVM\Frontend\Response;
use JVVM\Frontend\Request;
use JVVM\Utils\ID;
use JVVM\Utils\URI;

class Member {
    protected BMember $backend;
    protected Response $response;

    function __construct(Response $encoder) {
        $this->backend = new BMember();
        $this->response = $encoder;
    }

    static function id_filter (mixed $value):?ID {
        if (ctype_digit($value)) {
            $value = intval($value);
        }
        try {
            $id = new ID($value);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return NULL;
        }
        return $id;
    }

    function read(Request $request, array $params, array $args = []) {
        $this->response->open($request->get_uri());
        try {
            $object = $this->backend->get($params['user_id']);
            $this->response->emit($object, $request->get_uri());
        } catch (Exception $e) {
            $this->response->error($e);
        }
        $this->response->close();
    }
    
    function create(Request $request, array $params, array $args = []) {
        $body = $request->get_json_body();
        $status = 'active';
        if (isset($body->status)) {
            switch($body->status) {
                default: $status = 'active'; break;
                case 'dead':
                case 'inactive':
                    $status = $body->status;
                    break;

            }
        }
        $this->backend->create($body->firstname, $body->lastname, $status);
    }
    
    function edit(Request $request, array $params, array $args) {
        $body = $request->get_json_body();
        $this->backend->replace($params['user_id'], $body->firstname, $body->lastname);
    }
    
    function patch(Request $request, array $params, array $args) {
        $body = $request->get_json_body();
        $fields = [];
        $keys = array_keys(get_object_vars($body));
        foreach($keys as $key) {
            $fields[$key] = $body->{$key};
        }
        $this->backend->patch($params['user_id'], $fields);
    }

    function search(Request $request) {
        $this->response->open($request->get_uri());
        try {
            foreach($this->backend->search($request->get_uri()->get_query_string()) as $object) {
                $this->response->emit(
                    $object,
                    new URI(strval($request->get_uri()) . '/' . $object['id'])
                );
            }
        } catch (Exception $e) {
            $this->response->error($e);
        }
        $this->response->close();
    }

}