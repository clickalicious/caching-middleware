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

namespace Clickalicious\Caching\Middleware;

use Gpupo\Cache\CacheAwareTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Teapot\StatusCode\Http;
use Wandu\Http\Psr\Stream;
use Wandu\Http\Psr\Response;

/**
 * Class Cache.
 */
class Cache
{
    // Use the provided trait for functionality
    use CacheAwareTrait;

    /**
     * Callable factory for producing CacheItemInterface instances.
     *
     * @var callable
     */
    protected $cacheItemFactory;

    /**
     * Callable factory for returning a key for cache-item.
     *
     * @var callable
     */
    protected $cacheItemKeyFactory;

    /**
     * HTTP Method/Verb GET.
     *
     * @var string
     * @const
     */
    const HTTP_METHOD_GET = 'GET';

    /**
     * Cache constructor.
     *
     * @param CacheItemPoolInterface $cacheItemPoolInterface Instance of a PSR-6 Cache Pool
     * @param callable               $cacheItemFactory       Factory for producing CacheItemInterface instances
     * @param callable               $cacheItemKeyFactory    Factory for producing key for request instances
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
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
     *
     * @return Response|\Psr\Http\Message\ResponseInterface
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (self::HTTP_METHOD_GET !== $request->getMethod()) {
            return $next($request, $response);
        }

        return $this->handle($request, $response, $next);
    }

    /*------------------------------------------------------------------------------------------------------------------
    | SETTER, GETTER, ISSER & HASSER
    +-----------------------------------------------------------------------------------------------------------------*/

    /**
     * Setter for cacheItemFactory.
     *
     * @param callable $cacheItemFactory the cache item factory to set
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     */
    protected function setCacheItemFactory(callable $cacheItemFactory)
    {
        $this->cacheItemFactory = $cacheItemFactory;
    }

    /**
     * Setter for cacheItemFactory.
     *
     * @param callable $cacheItemFactory the cache item factory to set
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     *
     * @return $this Instance for chaining
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
     *
     * @return callable The CacheItemInterface factory if set, otherwise NULL
     */
    protected function getCacheItemFactory()
    {
        return $this->cacheItemFactory;
    }

    /**
     * Setter for cacheItemKeyFactory.
     *
     * @param callable $cacheItemKeyFactory the cache item factory to set
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     */
    protected function setCacheItemKeyFactory(callable $cacheItemKeyFactory)
    {
        $this->cacheItemKeyFactory = $cacheItemKeyFactory;
    }

    /**
     * Setter for cacheItemKeyFactory.
     *
     * @param callable $cacheItemKeyFactory the cache item factory to set
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     *
     * @return $this Instance for chaining
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
     *
     * @return callable The CacheItemInterface factory if set, otherwise NULL
     */
    protected function getCacheItemKeyFactory()
    {
        return $this->cacheItemKeyFactory;
    }

    /**
     * Fluent: Setter for cacheItemPool.
     *
     * @param CacheItemPoolInterface $cacheItemPool the cache item pool to set
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     *
     * @return $this Instance for chaining
     */
    protected function cacheItemPool(CacheItemPoolInterface $cacheItemPool)
    {
        $this->setCacheItemPool($cacheItemPool);

        return $this;
    }

    /**
     * Returns a cached Response by Request(Interface).
     *
     * @param RequestInterface $request the request to return cached response for
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     *
     * @return string|null The HTML for response as string if found, otherwise NULL
     *
     * @throws \Psr\Cache\InvalidArgumentException
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
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface      $response
     * @param callable                                 $next
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \InvalidArgumentException
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \RuntimeException
     */
    protected function handle(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        // @codeCoverageIgnoreStart
        if ($html = $this->getCachedResponseHtml($request)) {
            return $this->buildResponse($html, $response);
        }
        // @codeCoverageIgnoreEnd

        /** @var ResponseInterface $response */
        $response = $next($request, $response);

        if (Http::OK === $response->getStatusCode()) {
            $this->cacheResponse($request, $response);
        }

        return $response;
    }

    /**
     * Builds response instance with HTML body from HTML passed in.
     *
     * @param string                              $html     HTML used for response body
     * @param \Psr\Http\Message\ResponseInterface $response Response used as base for response
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    protected function buildResponse($html, ResponseInterface $response)
    {
        // @codeCoverageIgnoreStart
        $body = new Stream('php://memory', 'w');
        $body->write($html);
        $response = $response->withBody($body);

        return $response;
        // @codeCoverageIgnoreEnd
    }

    /**
     * Creates a (static) key from RequestInterface passed in.
     *
     * @param RequestInterface $request A request to return key for
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     *
     * @return string The key of the passed RequestInterface
     */
    protected function createKeyFromRequest(RequestInterface $request)
    {
        $keyFactory = $this->getCacheItemKeyFactory();

        return $keyFactory($request);
    }

    /**
     * Creates a fresh CacheItemInterface instance from factory.
     *
     * @param string $key Key to set as key of CacheItemInterface
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     *
     * @return CacheItemInterface Fresh instance of a CacheItem
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
     */
    protected function cacheResponse(RequestInterface $request, ResponseInterface $response)
    {
        $cacheItem = $this->createCacheItem($this->createKeyFromRequest($request));
        $value = $response->getBody()->__toString();
        $cacheItem->set($value);

        $this
            ->getCacheItemPool()
            ->save($cacheItem);
    }
}
