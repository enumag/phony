<?php

/*
 * This file is part of the Phony package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Phony\Mock\Proxy\Exception;

use Exception;
use PHPUnit_Framework_TestCase;

class UndefinedPropertyExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $className = 'ClassName';
        $name = 'property';
        $cause = new Exception();
        $exception = new UndefinedPropertyException($className, $name, $cause);

        $this->assertSame($className, $exception->className());
        $this->assertSame($name, $exception->name());
        $this->assertSame("Undefined property ClassName::property().", $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($cause, $exception->getPrevious());
    }
}