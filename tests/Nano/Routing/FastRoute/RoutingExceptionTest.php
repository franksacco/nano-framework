<?php
/**
 * Nano Framework
 *
 * @package   Nano
 * @author    Francesco Saccani <saccani.francesco@gmail.com>
 * @copyright Copyright (c) 2019 Francesco Saccani
 * @version   1.0
 */

declare(strict_types=1);

namespace Nano\Routing\FastRoute;

use PHPUnit\Framework\TestCase;

class RoutingExceptionTest extends TestCase
{
    public function testForNotLoadedData()
    {
        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('Routing data has not yet been loaded');

        throw RoutingException::forNotLoadedData();
    }

    public function testForInvalidCacheDir()
    {
        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('Unable to load routing data: the cache directory given is not valid');

        throw RoutingException::forInvalidCacheDir();
    }

    public function testForInvalidCacheFile()
    {
        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('Unable to load routing data: invalid cache file(s)');

        throw RoutingException::forInvalidCacheFile();
    }

    public function testForNotFoundRoute()
    {
        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('The route with name "test" was not found');

        throw RoutingException::forNotFoundRoute('test');
    }

    public function testForNotEnoughParams()
    {
        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('Not enough parameters given for route "test"');

        throw RoutingException::forNotEnoughParams('test');
    }

    public function testForTooManyParams()
    {
        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('Too many parameters given for route "test"');

        throw RoutingException::forTooManyParams('test');
    }
}