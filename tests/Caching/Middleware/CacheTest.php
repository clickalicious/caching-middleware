<?php

/**
 * (The MIT license)
 * Copyright 2017 clickalicious, Benjamin Carl
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

namespace Caching\Middleware;

use Gpupo\Cache\CacheItemPool;
use Gpupo\Cache\CacheItem;
use Wandu\Http\Psr\ServerRequest as Request;
use Psr\Http\Message\RequestInterface;
use Wandu\Http\Psr\Response;
use Wandu\Http\Psr\Stream;
use Wandu\Http\Psr\Uri;

/**
 * Class CacheTest
 *
 * @package CacheTest
 */
class CacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Instance of Cache.
     *
     * @var Cache
     */
    protected $cachingMiddleware;

    /**
     * The body for testing as stream resource.
     *
     * @var Stream
     */
    protected $body;

    /**
     * The emulated/faked next callable.
     *
     * @var callable
     */
    protected $next;

    /**
     * $_SERVER.
     *
     * @var array
     */
    protected $server;

    /**
     * $_COOKIES.
     *
     * @var array
     */
    protected $cookie;

    /**
     * $_REQUEST.
     *
     * @var array
     */
    protected $request;

    /**
     * $_FILES.
     *
     * @var array
     */
    protected $files;

    /**
     * Set up for testing.
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     */
    protected function setUp()
    {
        // Get a null driver (testing) based instance of cache pool
        $cacheItemPool = new CacheItemPool('Null');

        // Dummy factory One
        $cacheItemFactory = function ($key) {
            return new CacheItem($key);
        };

        // Dummy factory Two
        $cacheItemKeyFactory = function (RequestInterface $request) {
            return sha1(serialize($request));
        };

        // Create instance of Cache for testing
        $this->cachingMiddleware = new Cache($cacheItemPool, $cacheItemFactory, $cacheItemKeyFactory);

        // Create a default body for testing
        $this->body = new Stream('php://memory', 'w');
        $this->body->write('<html><head><title>Test</title></head><body><h1>Hello World!</h1></body></html>');

        // Create fake next callable
        $this->next = function (Request $request, Response $response) {
            return $response;
        };

        // Map globals for inject in later use
        $this->server  = $_SERVER;
        $this->cookie  = $_COOKIE;
        $this->request = $_REQUEST;
        $this->files   = $_FILES;
    }

    /**
     * Tests: If the Cache can be instantiated properly.
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     */
    public function testInit()
    {
        $this->assertInstanceOf('Caching\Middleware\Cache', $this->cachingMiddleware);
    }

    /**
     * Tests: If the Cache is capable of handling a request.
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     */
    public function testHandleGetRequest()
    {
        // Retrieve caching middleware for call
        $cachingMiddleware = $this->cachingMiddleware;

        // Next
        $next = $this->next;

        /* @var Response $response */
        $response = $cachingMiddleware(
            new Request(
                $this->server,
                $this->request,
                [],
                $this->cookie,
                $this->files,
                [],
                'GET',
                new Uri(
                    '/phpunit/test'
                ),
                new Stream(),
                [],
                '1.1'
            ),
            new Response(
                200,
                $this->body,
                [],
                'OK',
                '1.1'
            ),
            $next
        );

        // Ensure that we retrieved a response compatible to interface of PSR (basic check)
        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
    }

    /**
     * Tests: If the Cache would skip a "not-GET" request.
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     */
    public function testSkipNonGetRequest()
    {
        // Retrieve caching middleware for call
        $cachingMiddleware = $this->cachingMiddleware;

        // Next
        $next = $this->next;

        /* @var Response $response */
        $response = $cachingMiddleware(
            new Request(
                $this->server,
                $this->cookie,
                $this->request,
                $this->files,
                [],
                [],
                'POST',
                new Uri(
                    '/phpunit/test'
                ),
                '1.1',
                []
            ),
            new Response(
                200,
                'OK',
                '1.1',
                [],
                $this->body
            ),
            $next
        );

        // Ensure that we retrieved a response compatible to interface of PSR (basic check)
        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
    }

    /**
     * Tests: If the CachePool can be retrieved like the contract says.
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     */
    public function testRetrieveCachePool()
    {
        // Retrieve caching middleware for call
        $cachingMiddleware = $this->cachingMiddleware;

        // Ensure that we retrieved a response compatible to interface of PSR (basic check)
        $this->assertInstanceOf('Psr\Cache\CacheItemPoolInterface', $cachingMiddleware->getCacheItemPool());
        $this->assertTrue($cachingMiddleware->hasCacheItemPool());
    }
}
