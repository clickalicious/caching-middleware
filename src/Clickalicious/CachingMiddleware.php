<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Clickalicious;

/**
 * CachingMiddleware
 *
 * CachingMiddleware.php - Implementation.
 *
 * PHP versions 5.6
 *
 * LICENSE:
 * CachingMiddleware - A caching middleware for PSR-7 stacks based on PSR
 * compatible cache implementations.
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

use Gpupo\Cache\CacheAwareTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Teapot\StatusCode\Http;
use Wandu\Http\Psr\Stream;
use Wandu\Http\Psr\Response;

/**
 * CachingMiddleware
 *
 * Implementation.
 *
 * @category   CachingMiddleware
 * @package    CachingMiddleware_Core
 * @author     Benjamin Carl <opensource@clickalicious.de>
 * @copyright  2015 - 2016 Benjamin Carl
 * @license    http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version    Git: $Id$
 * @link       http://github.com/clickalicious/CachingMiddleware
 */
class CachingMiddleware
{
    // Use the provided trait for functionality
    use CacheAwareTrait;

    /**
     * Callable factory for producing CacheItemInterface instances
     *
     * @var callable
     * @access protected
     */
    protected $cacheItemFactory;

    /**
     * Callable factory for returning a key for cache-item
     *
     * @var callable
     * @access protected
     */
    protected $cacheItemKeyFactory;

    /**
     * HTTP Method/Verb GET
     *
     * @var string
     * @access public
     * @const
     */
    const HTTP_METHOD_GET = 'GET';

    /**
     * CachingMiddleware constructor.
     *
     * @param CacheItemPoolInterface $cacheItemPoolInterface Instance of a PSR-6 Cache Pool
     * @param callable               $cacheItemFactory       Factory for producing CacheItemInterface instances
     * @param callable               $cacheItemKeyFactory    Factory for producing key for request instances
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     * @access public
     */
    public function __construct(
        CacheItemPoolInterface $cacheItemPoolInterface,
        callable $cacheItemFactory,
        callable $cacheItemKeyFactory
    ) {
        $this
            ->cacheItemPool($cacheItemPoolInterface)
            ->cacheItemFactory($cacheItemFactory)
            ->cacheItemKeyFactory($cacheItemKeyFactory);
    }

    /**
     * Invoke.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface      $response
     * @param callable                                 $next
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     * @return Response|\Psr\Http\Message\ResponseInterface
     * @access public
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (self::HTTP_METHOD_GET !== $request->getMethod()) {
            return $next($request, $response);
        }

        if ($html = $this->getCachedResponseHtml($request)) {

            $body = new Stream('php://memory', 'w');
            $body->write($html);
            $response = $response->withBody($body);

            return $response;
        }

        /** @var ResponseInterface $response */
        $response = $next($request, $response);

        if (Http::OK === $response->getStatusCode()) {
            $this->cacheResponse($request, $response);
        }

        return $response;
    }

    /*------------------------------------------------------------------------------------------------------------------
    | SETTER, GETTER, ISSER & HASSER
    +-----------------------------------------------------------------------------------------------------------------*/

    /**
     * Setter for cacheItemFactory.
     *
     * @param callable $cacheItemFactory The cache item factory to set.
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     * @return void
     * @access protected
     */
    protected function setCacheItemFactory(callable $cacheItemFactory)
    {
        $this->cacheItemFactory = $cacheItemFactory;
    }

    /**
     * Setter for cacheItemFactory.
     *
     * @param callable $cacheItemFactory The cache item factory to set.
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     * @return $this Instance for chaining
     * @access protected
     */
    protected function cacheItemFactory(callable $cacheItemFactory)
    {
        $this->setCacheItemFactory($cacheItemFactory);

        return $this;
    }

    /**
     * Getter for cacheItemFactory.
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     * @return callable|null The CacheItemInterface factory if set, otherwise NULL
     * @access protected
     */
    protected function getCacheItemFactory()
    {
        return $this->cacheItemFactory;
    }

    /**
     * Setter for cacheItemKeyFactory.
     *
     * @param callable $cacheItemKeyFactory The cache item factory to set.
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     * @return void
     * @access protected
     */
    protected function setCacheItemKeyFactory(callable $cacheItemKeyFactory)
    {
        $this->cacheItemKeyFactory = $cacheItemKeyFactory;
    }

    /**
     * Setter for cacheItemKeyFactory.
     *
     * @param callable $cacheItemKeyFactory The cache item factory to set.
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     * @return $this Instance for chaining
     * @access protected
     */
    protected function cacheItemKeyFactory(callable $cacheItemKeyFactory)
    {
        $this->setCacheItemKeyFactory($cacheItemKeyFactory);

        return $this;
    }

    /**
     * Getter for cacheItemKeyFactory.
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     * @return callable|null The CacheItemInterface factory if set, otherwise NULL
     * @access protected
     */
    protected function getCacheItemKeyFactory()
    {
        return $this->cacheItemKeyFactory;
    }

    /**
     * Fluent: Setter for cacheItemPool.
     *
     * @param CacheItemPoolInterface $cacheItemPool The cache item pool to set.
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     * @return $this Instance for chaining
     * @access protected
     */
    protected function cacheItemPool(CacheItemPoolInterface $cacheItemPool)
    {
        $this->setCacheItemPool($cacheItemPool);

        return $this;
    }

    /**
     * Returns a cached Response by Request(Interface).
     *
     * @param RequestInterface $request The request to return cached response for.
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     * @return string|null The HTML for response as string if found, otherwise NULL
     * @access protected
     */
    protected function getCachedResponseHtml(RequestInterface $request)
    {
        return $this
            ->getCacheItemPool()
            ->getItem($this->createKeyFromRequest($request))
            ->get();
    }

    /*------------------------------------------------------------------------------------------------------------------
    | INTERNAL API
    +-----------------------------------------------------------------------------------------------------------------*/

    /**
     * Creates a (static) key from RequestInterface passed in.
     *
     * @param RequestInterface $request A request to return key for
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     * @return string The key of the passed RequestInterface
     * @access protected
     */
    protected function createKeyFromRequest(RequestInterface $request)
    {
        $keyFactory = $this->getCacheItemKeyFactory();

        return $keyFactory($request);
    }

    /**
     * Creates a fresh CacheItemInterface instance from factory.
     *
     * @param mixed $key Key to set as key of CacheItemInterface
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     * @return CacheItemInterface Fresh instance of a CacheItem
     * @access protected
     */
    protected function createCacheItem($key)
    {
        $factory = $this->getCacheItemFactory();

        return $factory($key);
    }

    /**
     * Caches a response by Request & Response.
     *
     * @param RequestInterface  $request  Request as identifier
     * @param ResponseInterface $response Response to cache
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     * @return void
     * @access protected
     */
    protected function cacheResponse(RequestInterface $request, ResponseInterface $response)
    {
        $cacheItem = $this->createCacheItem($this->createKeyFromRequest($request));
        $value     = $response->getBody()->__toString();
        $cacheItem->set($value);

        $this
            ->getCacheItemPool()
            ->save($cacheItem);
    }
}
