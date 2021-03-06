<?php

declare(strict_types=1);

namespace Eloquent\Phony\Mock\Exception;

use PHPUnit\Framework\TestCase;

class FinalMethodStubExceptionTest extends TestCase
{
    public function testException()
    {
        $className = 'ClassName';
        $name = 'method';
        $exception = new FinalMethodStubException($className, $name);

        $this->assertSame($className, $exception->className());
        $this->assertSame($name, $exception->name());
        $this->assertSame('The method ClassName::method() cannot be stubbed because it is final.', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}
