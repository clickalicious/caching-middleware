<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * CachingMiddleware
 *
 * demo.php - Demonstration of CachingMiddleware.
 *
 * PHP versions 5.5
 *
 * LICENSE:
 * CachingMiddleware - A caching middleware for PSR-7 stacks based on PSR
 * compatible cache implementations.
 *
 * Copyright (c) 2015 - 2016, Benjamin Carl - All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *   this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 * - All advertising materials mentioning features or use of this software
 *   must display the following acknowledgment: This product includes software
 *   developed by Benjamin Carl and other contributors.
 * - Neither the name Benjamin Carl nor the names of other contributors
 *   may be used to endorse or promote products derived from this
 *   software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * Please feel free to contact us via e-mail: opensource@clickalicious.de
 *
 * @category   CachingMiddleware
 * @package    CachingMiddleware_Demo
 * @author     Benjamin Carl <opensource@clickalicious.de>
 * @copyright  2015 - 2016 Benjamin Carl
 * @license    http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version    Git: $Id$
 * @link       http://clickalicious.github.com/CachingMiddleware/
 */

require_once 'vendor/autoload.php';

use Clickalicious\CachingMiddleware;
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
$queue[] = function(RequestInterface $request, ResponseInterface $response, callable $next) {

    $cacheItemFactory = function($key) {
        return new CacheItem($key);
    };

    $cacheItemKeyFactory = function(RequestInterface $request) {

        static $key = null;

        if (null === $key) {
            $uri     = $request->getUri();
            $slugify = new Slugify();
            $key     = $slugify->slugify(trim($uri->getPath(), '/').($uri->getQuery() ? '?'.$uri->getQuery() : ''));
        }

        return $key;
    };

    $cachingMiddleWare = new CachingMiddleware(
        new CacheItemPool('Filesystem'),
        $cacheItemFactory,
        $cacheItemKeyFactory
    );

    return $cachingMiddleWare($request, $response, $next);
};


// Create a Relay Runner instance ...
$runner = new Runner($queue);


// Test to cache
$body = new Wandu\Http\Psr\Stream('php://memory', 'w');
$body->write('<html><head><title>Demo</title></head><body><h1>Hello World!</h1></body></html>');


// ... and run it with the queue defined above
/* @var Response $response */
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

echo $response->getBody();
