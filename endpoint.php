<?php
require __DIR__ . '/vendor/autoload.php';

use JVVM\Frontend\Content;
use JVVM\Frontend\Router;
use JVVM\Backend\PDO;
use JVVM\Backend\Session;
use JVVM\Frontend\Response;
use JVVM\Utils\Exceptions\MalformedRoute;
use JVVM\Utils\URI;
use JVVM\Utils\HTTPMethod;
use JVVM\Utils\RouterMethod;

ob_start();
try {
    $pdo = new PDO('mysql:host=localhost;dbname=jvvm', 'jvvm', 'jvvm');

    $response = new Response();
    $router = new Router();
    $router->set_filter('uid', [Content\Member::class, 'id_filter']);
    $session = new Session();

    $registering_function = 'register_limited';
    if ($session->has_session()) {
        $registering_function = 'register';
    }

    $router->add(
        RouterMethod::redirect(),
        new URI('user/{*}'),
        fn() => new URI('member')
    );

    $uri = URI::fromRequest($_SERVER['REQUEST_URI']);
    /* Register only a subset of route */
    $class = '';
    error_log($uri->get_base());
    switch($uri->get_base()) {
        case Content\Member::get_base(): $class = Content\Member::class; break;
        case Content\Register::get_base(): $class = Content\Register::class; break;

    }

    if (!empty($class)) {
        call_user_func([$class, $registering_function], $router, $response);
    }
    $router->run($uri);
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
}

ob_end_flush();

exit(0);
