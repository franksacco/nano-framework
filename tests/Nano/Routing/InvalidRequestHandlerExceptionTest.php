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

namespace Nano\Routing;

use PHPUnit\Framework\TestCase;

class InvalidRequestHandlerExceptionTest extends TestCase
{
    public function testForInvalidClass()
    {
        $this->expectException(InvalidRequestHandlerException::class);
        $this->expectExceptionMessage('Unable to resolve class class for a' .
            ' non-existent class or a DI container without auto-wiring');

        throw InvalidRequestHandlerException::forInvalidClass('class');
    }

    public function testForNonObject()
    {
        $this->expectException(InvalidRequestHandlerException::class);
        $this->expectExceptionMessage('Unable to resolve class class for a non-object result from the container');

        throw InvalidRequestHandlerException::forNonObject('class');
    }

    public function testForInvalidHandler()
    {
        $this->expectException(InvalidRequestHandlerException::class);
        $this->expectExceptionMessage('Invalid request handler provided');

        throw InvalidRequestHandlerException::forInvalidHandler();
    }

    public function testForInvalidResult()
    {
        $this->expectException(InvalidRequestHandlerException::class);
        $this->expectExceptionMessage('The request handler must produce a ' .
            'Psr\Http\Message\ResponseInterface or a string, got NULL instead');

        throw InvalidRequestHandlerException::forInvalidResult(null);
    }
}