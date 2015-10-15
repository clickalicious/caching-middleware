<?php

namespace Clickalicious;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;


class CachingMiddleware
{
    protected $cacheDir = 'data/cache/pages';


    public function __construct()
    {

    }


    protected function getCacheFile(RequestInterface $request)
    {
        $uri = $request->getUri();
        return 'cached-'
        . trim($uri->getPath(), '/')
        . ($uri->getQuery() ? '?' . $uri->getQuery() : '')
        . '.html';
    }

    private function getCachedHtml(RequestInterface $request)
    {
        $fullPath = $this->cacheDir . '/' . $this->getCacheFile($request);
        if (file_exists($fullPath) && filemtime($fullPath) > time() - 4 * 3600) {
            return file_get_contents($fullPath);
        }
        return null;
    }

    private function cacheResponse(RequestInterface $request, ResponseInterface $response)
    {
        $cacheFilePath = $this->cacheDir . '/' . $this->getCacheFile($request);

        if (!file_exists(dirname($cacheFilePath))) {
            mkdir(dirname($cacheFilePath), 0777, true);
        }

        file_put_contents($cacheFilePath, $response->getBody()->__toString());
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($request->getMethod() != 'GET') {
            return $next($request, $response);
        }

        if ($html = $this->getCachedHtml($request)) {
            return new HtmlResponse($html);
        }

        /** @var ResponseInterface $response */
        $response = $next($request, $response);

        if ($response->getStatusCode() == 200) {
            $this->cacheResponse($request, $response);
        }

        return $response;
    }
}
