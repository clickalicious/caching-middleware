<img src="https://avatars0.githubusercontent.com/u/26927954?v=3&s=80" align="right" />
---

![Logo of Caching-Middleware](docs/logo-large.png)

The `PSR-6` **caching middleware** compatible to `PSR-7` stacks.

| [![Build Status](https://img.shields.io/travis/clickalicious/caching-middleware.svg)](https://travis-ci.org/clickalicious/caching-middleware) 	| [![Codacy grade](https://img.shields.io/codacy/grade/a4f484985bd74c82b98ded7e1b0f43af.svg)](https://www.codacy.com/app/benjamin-carl/caching-middleware?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=clickalicious/caching-middleware&amp;utm_campaign=Badge_Grade) 	| [![Codacy coverage](https://img.shields.io/codacy/coverage/a4f484985bd74c82b98ded7e1b0f43af.svg)](https://www.codacy.com/app/benjamin-carl/caching-middleware?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=clickalicious/caching-middleware&amp;utm_campaign=Badge_Grade) 	| [![clickalicious open source](https://img.shields.io/badge/clickalicious-open--source-green.svg?style=flat)](https://clickalicious.de/) 	|
|---	|---	|---	|---	|
| [![GitHub release](https://img.shields.io/github/release/clickalicious/caching-middleware.svg?style=flat)](https://github.com/clickalicious/caching-middleware/releases) 	| [![license](https://img.shields.io/github/license/mashape/apistatus.svg)](https://opensource.org/licenses/MIT)  	| [![Issue Stats](https://img.shields.io/issuestats/i/github/clickalicious/caching-middleware.svg)](https://github.com/clickalicious/caching-middleware/issues) 	| [![Dependency Status](https://dependencyci.com/github/clickalicious/webserver-daemon/badge)](https://dependencyci.com/github/clickalicious/webserver-daemon)  	|


## Table of Contents

- [Features](#features)
- [Example](#example)
- [Requirements](#requirements)
- [Philosophy](#philosophy)
- [Versioning](#versioning)
- [Roadmap](#roadmap)
- [Security-Issues](#security-issues)  
- [License »](LICENSE)


## Features

 - High performance (developed using a profiler)
 - Lightweight and high-quality codebase (following `PSR-1,2,4,7`)
 - 100% `PSR-7` compatible
 - `PSR-6` caching
 - Clean & well documented code
 - Unit-tested with a good coverage


## Example

Put a `Cache` in queue and use a `PSR-6 Cache` (Filesystem):

```php
/**
 * Fill queue for running "Cache"
 *
 * @param \Psr\Http\Message\ServerRequestInterface $request  Request (PSR) to process
 * @param \Psr\Http\Message\ResponseInterface      $response Response (PSR) to use
 * @param callable                                 $next     Next middleware in stack
 *
 * @return \Psr\Http\Message\ResponseInterface A PSR compatible response
 */
$queue[] = function (Request $request, Response $response, callable $next) {

    // Create cache item factory
    $cacheItemFactory = function ($key) {
        return new CacheItem($key);
    };

    // Create cache item key factory
    $cacheItemKeyFactory = function (Request $request) {
        static $key = null;
        if (null === $key) {
            $uri     = $request->getUri();
            $slugify = new Slugify();
            $key     = $slugify->slugify(trim($uri->getPath(), '/').($uri->getQuery() ? '?'.$uri->getQuery() : ''));
        }

        return $key;
    };

    // Get cache
    $cachingMiddleWare = new Clickalicious\Caching\Middleware\Cache(
        new CacheItemPool('Filesystem'),
        $cacheItemFactory,
        $cacheItemKeyFactory
    );

    return $cachingMiddleWare($request, $response, $next);
};
```


## Requirements

 - `PHP >= 5.6` (compatible up to PHP version 7.2)


## Philosophy

`Cache` is the PSR-7 compatible middleware based on PSR compatible cache implementations. `Cache` comes as prototype implementation and currently has alpha status. Try it, run it ... ♥ it ;)


## Versioning

For a consistent versioning we decided to make use of `Semantic Versioning 2.0.0` http://semver.org. Its easy to understand, very common and known from many other software projects.


## Roadmap
- [ ] Cache whole response instead just rendered HTML (Headers as well for example)
- [ ] Implement [flysystem](http://flysystem.thephpleague.com/ "flysystem") as Driver for PSR-Cache


[![Throughput Graph](https://graphs.waffle.io/clickalicious/caching-middleware/throughput.svg)](https://waffle.io/clickalicious/caching-middleware/metrics)


## Security Issues

If you encounter a (potential) security issue don't hesitate to get in contact with us `opensource@clickalicious.de` before releasing it to the public. So i get a chance to prepare and release an update before the issue is getting shared. Thank you!


## Participate & Share

... yeah. If you're a code monkey too - maybe we can build a force ;) If you would like to participate in either **Code**, **Comments**, **Documentation**, **Wiki**, **Bug-Reports**, **Unit-Tests**, **Bug-Fixes**, **Feedback** and/or **Critic** then please let us know as well!
<a href="https://twitter.com/intent/tweet?hashtags=&original_referer=http%3A%2F%2Fgithub.com%2F&text=Cache%20-%20PSR-7%20compatible%20stack%20implementation%20based%20on%20PSR-6.%20%40phpfluesterer%20%23caching-middleware%20%23php%20https%3A%2F%2Fgithub.com%2Fclickalicious%2Fcaching-middleware&tw_p=tweetbutton" target="_blank">
  <img src="http://jpillora.com/github-twitter-button/img/tweet.png"></img>
</a>


## Sponsors

Thanks to our sponsors and supporters:  

| JetBrains | Navicat |
|---|---|
| <a href="https://www.jetbrains.com/phpstorm/" title="PHP IDE :: JetBrains PhpStorm" target="_blank"><img src="https://resources.jetbrains.com/assets/media/open-graph/jetbrains_250x250.png" height="55"></img></a> | <a href="http://www.navicat.com/" title="Navicat GUI - DB GUI-Admin-Tool for MySQL, MariaDB, SQL Server, SQLite, Oracle & PostgreSQL" target="_blank"><img src="http://upload.wikimedia.org/wikipedia/en/9/90/PremiumSoft_Navicat_Premium_Logo.png" height="55" /></a>  |


###### Copyright
<div>Icons made by <a href="http://www.flaticon.com/authors/egor-rumyantsev" title="Egor Rumyantsev">Egor Rumyantsev</a> from <a href="http://www.flaticon.com" title="Flaticon">www.flaticon.com</a> is licensed by <a href="http://creativecommons.org/licenses/by/3.0/" title="Creative Commons BY 3.0" target="_blank">CC 3.0 BY</a></div>


[1]: https://packagist "packagist.org - Package registry of composer"
