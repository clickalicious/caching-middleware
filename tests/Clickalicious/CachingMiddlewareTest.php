<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Clickalicious;

/*
 * CachingMiddleware
 *
 * CachingMiddlewareTest.php - Tests of caching middleware implementation.
 *
 * PHP versions 5.5
 *
 * LICENSE:
 * CachingMiddleware - The caching middleware compatible to PSR-7 stack implementations.
 *
 * Copyright (c) 2005 - 2015, Benjamin Carl - All rights reserved.
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
 * @package    CachingMiddleware_Core
 * @author     Benjamin Carl <opensource@clickalicious.de>
 * @copyright  2015 - 2016 Benjamin Carl
 * @license    http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version    Git: $Id$
 * @link       http://github.com/clickalicious/CachingMiddleware
 */

use Gpupo\Cache\CacheItemPool;
use Wandu\Http\Psr\ServerRequest as Request;
use Psr\Http\Message\RequestInterface;
use Wandu\Http\Psr\Response;
use Wandu\Http\Psr\Stream;
use Wandu\Http\Psr\Uri;
use Gpupo\Cache\CacheItem;

/**
 * CachingMiddleware.
 *
 * Tests of caching middleware implementation.
 *
 * @category   CachingMiddleware
 *
 * @author     Benjamin Carl <opensource@clickalicious.de>
 * @copyright  2015 - 2016 Benjamin Carl
 * @license    http://www.opensource.org/licenses/bsd-license.php The BSD License
 *
 * @version    Git: $Id$
 *
 * @link       http://github.com/clickalicious/CachingMiddleware
 */
class CachingMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Instance of CachingMiddleware.
     *
     * @var CachingMiddleware
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

        // Create instance of CachingMiddleware for testing
        $this->cachingMiddleware = new CachingMiddleware($cacheItemPool, $cacheItemFactory, $cacheItemKeyFactory);

        // Create a default body for testing
        $this->body = new Stream('php://memory', 'w');
        $this->body->write('<html><head><title>Test</title></head><body><h1>Hello World!</h1></body></html>');

        // Create fake next callable
        $this->next = function (Request $request, Response $response) {
            return $response;
        };

        // Map globals for inject in later use
        $this->server = $_SERVER;
        $this->cookie = $_COOKIE;
        $this->request = $_REQUEST;
        $this->files = $_FILES;
    }

    /**
     * Tests: If the CachingMiddleware can be instantiated properly.
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     */
    public function testInit()
    {
        $this->assertInstanceOf('Clickalicious\CachingMiddleware', $this->cachingMiddleware);
    }

    /**
     * Tests: If the CachingMiddleware is cappable of handling a request.
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
                $this->cookie,
                $this->request,
                $this->files,
                [],
                [],
                'GET',
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
     * Tests: If the CachingMiddleware would skip a "not-GET" request.
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
