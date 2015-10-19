<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Clickalicious;

/**
 * CachingMiddleware
 *
 * CachingMiddleware.php - Implementation.
 *
 * PHP versions 5.5
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
 * @link       http://clickalicious.github.com/CachingMiddleware/
 */

use Gpupo\Cache\CacheItem;
use Gpupo\Cache\CacheAwareTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Teapot\StatusCode\Http;
use Cocur\Slugify\Slugify;
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
 * @link       http://clickalicious.github.com/CachingMiddleware/
 */
class CachingMiddleware
{
    // Use the provided trait for functionality
    use CacheAwareTrait;

    /**
     * The pool for items
     *
     * @var CacheItemPoolInterface
     */
    protected $cacheItemPool;

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
     * @param
     *
     * @author Benjamin Carl <opensource@clickalicious.de>
     * @access public
     */
    public function __construct(CacheItemPoolInterface $cacheItemPoolInterface, callable $cacheItemFactory)
    {
        $this
            ->cacheItemPool($cacheItemPoolInterface)
            ->cacheItemFactory($cacheItemFactory);
    }



    protected $cacheItemFactory;

    protected function setCacheItemFactory(callable $cacheItemFactory)
    {
        $this->cacheItemFactory = $cacheItemFactory;
    }

    protected function cacheItemFactory(callable $cacheItemFactory)
    {
        $this->setCacheItemFactory($cacheItemFactory);

        return $this;
    }

    protected function getCacheItemFactory()
    {
        return $this->cacheItemFactory;
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

        if ($html = $this->getCachedResponse($request)) {
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
    | INTERNAL API
    +-----------------------------------------------------------------------------------------------------------------*/

    protected function setCacheItemPool(CacheItemPoolInterface $cacheItemPool)
    {
        $this->cacheItemPool = $cacheItemPool;
    }

    protected function cacheItemPool(CacheItemPoolInterface $cacheItemPool)
    {
        $this->setCacheItemPool($cacheItemPool);

        return $this;
    }

    protected function getCacheItemPool()
    {
        return $this->cacheItemPool;
    }

    protected function getId(RequestInterface $request)
    {
        $uri     = $request->getUri();
        $slugify = new Slugify();

        return $slugify->slugify(
            trim($uri->getPath(), '/').($uri->getQuery() ? '?'.$uri->getQuery() : '')
        );
    }

    protected function getCachedResponse(RequestInterface $request)
    {
        return $this
            ->getCacheItemPool()
            ->getItem($this->getId($request))
                ->get();
    }

    protected function cacheResponse(RequestInterface $request, ResponseInterface $response)
    {
        $cacheItem = new CacheItem($this->getId($request));
        $value     = $response->getBody()->__toString();
        $cacheItem->set($value);

        $this
            ->getCacheItemPool()
            ->save($cacheItem);
    }
}
