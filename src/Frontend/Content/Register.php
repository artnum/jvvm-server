<?php

namespace JVVM\Frontend\Content;

use JVVM\Backend\Content\Member;
use JVVM\Auth\Simple;
use JVVM\Backend\PDO;
use JVVM\Frontend\IFrontend;
use JVVM\Frontend\Response;
use JVVM\Frontend\Request;
use JVVM\Frontend\Router;
use JVVM\Utils\HTTPMethod;
use JVVM\Utils\URI;

class Register implements IFrontend {
    static private string $route_base = 'register';
    protected Member $backend;
    protected Response $response;

    function __construct(Response $encoder) {
        $this->backend = new Member();
        $this->response = $encoder;
    }

    static function set_base(string $base):string {
        self::$route_base = $base;
        return self::$route_base;
    }
    static function get_base():string {
        return self::$route_base;
    }

    static function register(
            Router $router,
            Response $response,
            ?IFrontend $instance = NULL
    ) {
        self::register_limited($router, $response);
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
            new URI($base . '/create'),
            [$instance, 'create']
        );
    }

    function create(Request $request, array $params, array $args = []) {
        $body = $request->get_json_body();
        $status = 'register';
        $id = $this->backend->create($body->firstname, $body->lastname, $status);
        
        $auth = new Simple(PDO::getInstance());
        $auth->update($id, $body->identifier, $body->password);
        $this->response->open($request->get_uri());
        $this->response->emit([
            'id' => strval($id)
        ]);
        $this->response->close();
    }

}