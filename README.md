<img src="https://avatars2.githubusercontent.com/u/514566?v=3&u=4615dfc4970d93dea5d3eaf996b7903ee6e24e20&s=140" align="right" />
---

![Logo of Caching Middleware](docs/logo-large.png)

The `PSR-6` **Caching Middleware** compatible to `PSR-7` stacks.

| [![Build Status](https://img.shields.io/travis/clickalicious/CachingMiddleware.svg)](https://travis-ci.org/clickalicious/CachingMiddleware) 	| [![Scrutinizer](https://img.shields.io/scrutinizer/g/clickalicious/CachingMiddleware.svg)](http://clickalicious.github.io/CachingMiddleware/) 	| [![Code Coverage](https://scrutinizer-ci.com/g/clickalicious/CachingMiddleware/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/clickalicious/CachingMiddleware/?branch=master) 	| [![clickalicious open source](https://img.shields.io/badge/clickalicious-open--source-green.svg?style=flat)](https://www.clickalicious.de/) 	|
|---	|---	|---	|---	|
| [![GitHub release](https://img.shields.io/github/release/clickalicious/CachingMiddleware.svg?style=flat)](https://github.com/clickalicious/CachingMiddleware/releases) 	| [![Waffle.io](https://img.shields.io/waffle/label/clickalicious/CachingMiddleware/in%20progress.svg)](https://waffle.io/clickalicious/CachingMiddleware)  	| [![SensioLabsInsight](https://insight.sensiolabs.com/projects/2448be05-7ef4-45ae-b800-7965209f47a0/mini.png)](https://insight.sensiolabs.com/projects/2448be05-7ef4-45ae-b800-7965209f47a0) 	| [![Packagist](https://img.shields.io/packagist/l/clickalicious/CachingMiddleware.svg?style=flat)](https://opensource.org/licenses/BSD-3-Clause)  	|


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
 - Lightweight and high-quality codebase (following `PSR-0,1,2,7`)
 - 100% `PSR-7` compatible
 - `PSR-6` caching
 - Clean & well documented code
 - Unit-tested with a good coverage


## Example

Put a `CachingMiddleware` in queue and use a `PSR-6 Cache` (Filesystem):

```php
/**
 * Fill queue for running "CachingMiddleware"
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
    $cachingMiddleWare = new CachingMiddleware(
        new CacheItemPool('Filesystem'),
        $cacheItemFactory,
        $cacheItemKeyFactory
    );

    return $cachingMiddleWare($request, $response, $next);
};
```


## Requirements

 - `PHP >= 5.6` (compatible up to PHP version 7)


## Philosophy

`CachingMiddleware` is the PSR-7 compatible middleware based on PSR compatible cache implementations. `CachingMiddleware` comes as prototype implementation and currently has alpha status. Try it, run it ... ♥ it ;)


## Versioning

For a consistent versioning we decided to make use of `Semantic Versioning 2.0.0` http://semver.org. Its easy to understand, very common and known from many other software projects.


## Roadmap

- [x] Target stable release `1.0.0`
- [x] `>= 90%` test coverage
- [ ] Cache whole response instead just rendered HTML (Headers as well for example)
- [ ] Implement [flysystem](http://flysystem.thephpleague.com/ "flysystem") as Driver for PSR-Cache


[![Throughput Graph](https://graphs.waffle.io/clickalicious/CachingMiddleware/throughput.svg)](https://waffle.io/clickalicious/CachingMiddleware/metrics)


## Security Issues

If you encounter a (potential) security issue don't hesitate to get in contact with us `opensource@clickalicious.de` before releasing it to the public. So i get a chance to prepare and release an update before the issue is getting shared. Thank you!


## Participate & Share

... yeah. If you're a code monkey too - maybe we can build a force ;) If you would like to participate in either **Code**, **Comments**, **Documentation**, **Wiki**, **Bug-Reports**, **Unit-Tests**, **Bug-Fixes**, **Feedback** and/or **Critic** then please let us know as well!
<a href="https://twitter.com/intent/tweet?hashtags=&original_referer=http%3A%2F%2Fgithub.com%2F&text=CachingMiddleware%20-%20PSR-7%20compatible%20stack%20implementation%20based%20on%20PSR-6.%20%40phpfluesterer%20%23CachingMiddleware%20%23php%20https%3A%2F%2Fgithub.com%2Fclickalicious%2FCachingMiddleware&tw_p=tweetbutton" target="_blank">
  <img src="http://jpillora.com/github-twitter-button/img/tweet.png"></img>
</a>


## Sponsors

Thanks to our sponsors and supporters:  

| JetBrains | Navicat |
|---|---|
| <a href="https://www.jetbrains.com/phpstorm/" title="PHP IDE :: JetBrains PhpStorm" target="_blank"><img src="https://resources.jetbrains.com/assets/media/open-graph/jetbrains_250x250.png" height="55"></img></a> | <a href="http://www.navicat.com/" title="Navicat GUI - DB GUI-Admin-Tool for MySQL, MariaDB, SQL Server, SQLite, Oracle & PostgreSQL" target="_blank"><img src="http://upload.wikimedia.org/wikipedia/en/9/90/PremiumSoft_Navicat_Premium_Logo.png" height="55" /></a>  |


###### Copyright
Icons made by <a href="http://www.flaticon.com/authors/egor-rumyantsev" title="Egor Rumyantsev">Egor Rumyantsev</a> licensed by <a href="http://creativecommons.org/licenses/by/3.0/" title="Creative Commons BY 3.0">CC BY 3.0</a>


[1]: https://packagist "packagist.org - Package registry of composer"
