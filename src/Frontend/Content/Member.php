<?php

namespace JVVM\Frontend\Content;

use JVVM\Backend\Content\Member as BMember;
use Exception;
use JVVM\Auth\Auth;
use JVVM\Auth\Simple;
use JVVM\Backend\PDO;
use JVVM\Frontend\Response;
use JVVM\Frontend\Request;
use JVVM\Frontend\Router;
use JVVM\Utils\ID;
use JVVM\Utils\URI;
use JVVM\Utils\HTTPMethod;
use JVVM\Frontend\Content;
use JVVM\Frontend\IFrontend;

class Member implements IFrontend {
    protected BMember $backend;
    protected Response $response;
    static private string $route_base = 'member';

    static function set_base(string $base):string {
        self::$route_base = $base;
        return self::$route_base;
    }
    static function get_base():string {
        return self::$route_base;
    }

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

    static function register(
            Router $router,
            Response $response,
            ?IFrontend $instance = NULL
    ) {
        $base = self::$route_base;
        $instance = is_null($instance) ? new self($response) : $instance;
        $router->add(
            HTTPMethod::get(),
            new URI($base . '/{uid:user_id}'),
            [$instance, 'read']
        );
        $router->add(
            HTTPMethod::put() | HTTPMethod::post(),
            new URI($base . '/{uid:user_id}'),
            [$instance,'edit']
        );
        $router->add(
            HTTPMethod::put() | HTTPMethod::post(),
            new URI($base . '/'),
            [$instance, 'create']
        );
        $router->add(
            HTTPMethod::get(),
            new URI($base . '/'),
            [$instance,'search']
        );
        $router->add(
            HTTPMethod::patch(),
            new URI($base . '/{uid:user_id}'),
            [$instance, 'patch']
        );
        /* We register route for limited access */
        self::register_limited($router, $response, $instance);
    }

    static function register_limited(
            Router $router,
            Response $response,
            ?IFrontend $instance = NULL
    ) {
        $base = self::$route_base;
        $instance = is_null($instance) ? new self($response) : $instance;
        $router->add(
            HTTPMethod::post(),
            new URI($base . '/{any:identifier}/login'),
            [$instance, 'login']
        );
    }

    function login(Request $request, array $params, array $args) {
        $body = $request->get_json_body();
        $this->response->open($request->get_uri());
        if (!isset($body->auth_type)) {
            $this->response->emit(['auth' => 'failed']);
            return $this->response->close();
        }
        $auth = Auth::getIntance($body->auth_type);
        $session = $auth->login(
            $params['identifier'],
            $body->parameter1 ?? '',
            $body->parameter2 ?? '',
            $body->parameter3 ?? '',
            $body->parameter4 ?? ''
        );
        $this->response->emit(['auth' => strval($session)]);
        $this->response->close();
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
        $id = $this->backend->create($body->firstname, $body->lastname, $status);
        
        $auth = new Simple(PDO::getInstance());
        $auth->update($id, $body->identifier, $body->password);
        $this->response->open($request->get_uri());
        $this->response->emit([
            'id' => strval($id)
        ]);
        $this->response->close();
    }

    
    function accept(Request $request, array $params, array $args) {
        $this->backend->patch($params['user_id'], ['status' => 'active']);
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