<?php

/**
 * (The MIT license)
 * Copyright 2017 clickalicious, Benjamin Carl.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
 * BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

require_once dirname(__FILE__, 2).'/vendor/autoload.php';

use Clickalicious\Caching\Middleware\Cache;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Wandu\Http\Psr\ServerRequest as Request;
use Wandu\Http\Psr\Response;
use Wandu\Http\Psr\Uri;
use Relay\Runner;
use Gpupo\Cache\CacheItemPool;
use Gpupo\Cache\CacheItem;
use Cocur\Slugify\Slugify;

// Build queue for running middleware through relay
$queue[] = function (RequestInterface $request, ResponseInterface $response, callable $next) {

    $cacheItemFactory = function ($key) {
        return new CacheItem($key);
    };

    $cacheItemKeyFactory = function (RequestInterface $request) {

        static $key = null;

        if (null === $key) {
            $uri = $request->getUri();
            $slugify = new Slugify();
            $key = $slugify->slugify(trim($uri->getPath(), '/').($uri->getQuery() ? '?'.$uri->getQuery() : ''));
        }

        return $key;
    };

    $cachingMiddleWare = new Cache(
        new CacheItemPool('Filesystem'),
        $cacheItemFactory,
        $cacheItemKeyFactory
    );

    return $cachingMiddleWare($request, $response, $next);
};

// Create a Relay Runner instance ...
$runner = new Runner($queue);

// Replay protocol version from request ...
$protocolVersion = explode('/', $_SERVER['SERVER_PROTOCOL'])[1];

// Dummy content to be cached ...
$body = new Wandu\Http\Psr\Stream('php://memory', 'w');
$body->write('<html><head><title>Demo</title></head><body><h1>Hello World!</h1></body></html>');

// ... and run it with the queue defined above
/* @var Response $response */
$response = $runner(
    new Request(
        $_SERVER,
        $_REQUEST,
        [],
        $_COOKIE,
        $_FILES,
        [],
        $_SERVER['REQUEST_METHOD'],
        new Uri(
            $_SERVER['REQUEST_URI']
        ),
        new \Wandu\Http\Psr\Stream(),
        getallheaders(),
        $protocolVersion
    ),
    new Response(
        200,
        $body,
        [],
        Response::HTTP_STATUS_OK,
        $protocolVersion
    )
);

echo $response->getBody();
