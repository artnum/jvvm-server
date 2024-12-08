<?php
require __DIR__ . '/vendor/autoload.php';

use JVVM\Frontend\Content;
use JVVM\Frontend\Router;
use JVVM\Backend\PDO;
use JVVM\Frontend\Response;
use JVVM\Utils\URI;
use JVVM\Utils\HTTPMethod;

ob_start();
try {
    $pdo = new PDO('mysql:host=localhost;dbname=jvvm', 'jvvm', 'jvvm');

    $response = new Response();
    $router = new Router();

    $router->set_filter('uid', [Content\Member::class, 'id_filter']);

    $router->add(
        HTTPMethod::get(),
        new URI('$api/user/{uid:user_id}'),
        [new Content\Member($response), 'read']
    );
    $router->add(
        HTTPMethod::put() | HTTPMethod::post(),
        new URI('$api/user/{uid:user_id}'),
        [new Content\Member($response),'edit']
    );
    $router->add(
        HTTPMethod::put() | HTTPMethod::post(),
        new URI('$api/user/'),
        [new Content\Member($response), 'create']
    );
    $router->add(
        HTTPMethod::get(),
        new URI('$api/user/'),
        [new Content\Member($response),'search']
    );
    $router->add(
        HTTPMethod::patch(),
        new URI('$api/user/{uid:user_id}'),
        [new Content\Member($response), 'patch']
    );

    $router->run(new URI($_SERVER['REQUEST_URI']));
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
}

ob_end_flush();

exit(0);
