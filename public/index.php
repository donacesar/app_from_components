<?php

//var_dump($_SERVER); die;
if( !session_id() ) @session_start();
require __DIR__ . '/../vendor/autoload.php';

use League\Plates\Engine;
use Delight\Auth\Auth;
use \Tamtamchik\SimpleFlash\Flash;

$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/', ['App\Controllers\User', 'index']);
    $r->addRoute('GET', '/page_login', ['App\Controllers\User', 'page_login']);
    $r->addRoute('POST', '/login', ['App\Controllers\User', 'login']);
    $r->addRoute('GET', '/logout', ['App\Controllers\User', 'logout']);
    $r->addRoute('GET', '/page_create', ['App\Controllers\User', 'page_create']);
    $r->addRoute('POST', '/create', ['App\Controllers\User', 'create']);
    $r->addRoute('GET', '/page_register', ['App\Controllers\Registration', 'page_register']);
    $r->addRoute('POST', '/registration', ['App\Controllers\Registration', 'register']);
    $r->addRoute('GET', '/page_edit/{id:\d+}', ['App\Controllers\User', 'page_edit']);
    $r->addRoute('POST', '/edit', ['App\Controllers\User', 'edit']);
    $r->addRoute('GET', '/user/{id:\d+}', ['App\Controllers\User', 'page_user']);
    $r->addRoute('GET', '/page_security/{id:\d+}', ['App\Controllers\User', 'page_security']);
    $r->addRoute('POST', '/security', ['App\Controllers\User', 'security']);
    $r->addRoute('GET', '/page_status/{id:\d+}', ['App\Controllers\User', 'page_status']);
    $r->addRoute('POST', '/status', ['App\Controllers\User', 'status']);
    $r->addRoute('GET', '/page_media/{id:\d+}', ['App\Controllers\User', 'page_media']);
    $r->addRoute('POST', '/media_handler', ['App\Controllers\User', 'media_handler']);
    $r->addRoute('GET', '/delete/{id:\d+}', ['App\Controllers\User', 'delete']);
    // {id} must be a number (\d+)
    //$r->addRoute('GET', '/user/{id:\d+}', 'get_user_handler');
    //$r->addRoute('GET', '/user/{id:\d+}/company/classes/school/{number:\d+}', ['App\Controllers\HomeController', 'about']);
    // The /{title} suffix is optional
    $r->addRoute('GET', '/articles/{id:\d+}[/{title}]', 'get_article_handler');
});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$flash = new Flash();

// Instance of DI-container
$containerBuilder = new \DI\ContainerBuilder();
$containerBuilder->addDefinitions( [
    Engine::class => function () {
        return new Engine(__DIR__ . '/../src/views');
    },
    PDO::class => function() {
        return new PDO('mysql:host=localhost;dbname=my_database2;charset=utf8', 'root', 'root');
    },
    Auth::class => function($container){
        return new Auth($container->get('PDO'));
    }
]);
$container = $containerBuilder->build();


$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // ... 404 Not Found
        $flash->message('404. Страница не найдена.', 'error');
        $container->call(['App\Controllers\Error', 'index']);
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        // ... 405 Method Not Allowed
        $flash->message('405. Метод не разрешен.', 'error');
        $container->call(['App\Controllers\Error', 'index']);
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        // ... call $handler with $vars
        $container->call($routeInfo[1], [$routeInfo[2]]);
        break;
}
