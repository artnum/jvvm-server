<?php

namespace JVVM\Frontend;

use JVVM\Frontend\Response;
use JVVM\Frontend\Router;

Interface IFrontend {
    function __construct(Response $response);
    static function get_base():string;
    static function register(
        Router $router,
        Response $response,
        ?self $instance = NULL
    );
    static function register_limited(
        Router $router,
        Response $response,
        ?self $instance = NULL
    );
}