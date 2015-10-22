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
     * Tests: If the CachingMiddleware can be instantiated properly.
     */
    public function testInit()
    {
        // Get a null driver (testing) based instance of cache pool
        $cacheItemPool = new CacheItemPool('Null');

        // Dummy factory One
        $factoryOne = function () {
            return 1;
        };

        // Dummy factory Two
        $factoryTwo = function () {
            return 2;
        };

        $instance = new CachingMiddleware($cacheItemPool, $factoryOne, $factoryTwo);

        $this->assertInstanceOf('Clickalicious\CachingMiddleware', $instance);
    }
}
