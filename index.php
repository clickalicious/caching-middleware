<?php

require_once 'vendor/autoload.php';

use Clickalicious\CachingMiddleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Wandu\Http\Psr\ServerRequest as Request;
use Wandu\Http\Psr\Response;
use Wandu\Http\Psr\Uri;
use Relay\Runner;

// Build queue for running middleware through relay
$queue[] = function(RequestInterface $request, ResponseInterface $response, callable $next) {
    $cachingMiddleWare = new CachingMiddleware();
    return $cachingMiddleWare($request, $response, $next);
};

// Create a Relay Runner instance ...
$runner = new Runner($queue);

// Test to cache
$body = new Wandu\Http\Psr\Stream('php://memory', 'w');
$body->write('Aloha!');

// ... and run it with the queue defined above
$response = $runner(
    new Request(
        $_SERVER,
        $_COOKIE,
        $_REQUEST,
        $_FILES,
        [],
        [],
        $_SERVER['REQUEST_METHOD'],
        new Uri(
            $_SERVER['REQUEST_URI']
        ),
        '1.1',
        getallheaders()
    ),
    new Response(
        200,
        'OK',
        '1.1',
        [],
        $body
    )
);
