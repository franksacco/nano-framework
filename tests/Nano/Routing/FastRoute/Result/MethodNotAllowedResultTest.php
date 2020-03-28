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

namespace Nano\Routing\FastRoute\Result;

use PHPUnit\Framework\TestCase;

class MethodNotAllowedResultTest extends TestCase
{
    public function testMethodNotAllowedResult()
    {
        $allowedMethods = ['GET', 'POST', 'HEAD'];
        $result = new MethodNotAllowedResult($allowedMethods);

        $this->assertSame($allowedMethods, $result->getAllowedMethods());
    }
}