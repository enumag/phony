<?php

/*
 * This file is part of the Phony package.
 *
 * Copyright © 2017 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Phony\Spy;

use Eloquent\Phony\Call\Arguments;
use Eloquent\Phony\Invocation\Invoker;
use Eloquent\Phony\Reflection\FeatureDetector;
use Eloquent\Phony\Test\TestCallFactory;
use Generator;
use PHPUnit\Framework\TestCase;

class SpyDataWithGeneratorsTest extends TestCase
{
    protected function setUp()
    {
        $this->callback = 'implode';
        $this->label = 'label';
        $this->callFactory = new TestCallFactory();
        $this->invoker = new Invoker();
        $this->callEventFactory = $this->callFactory->eventFactory();
        $this->featureDetector = FeatureDetector::instance();
        $this->generatorSpyFactory = new GeneratorSpyFactory($this->callEventFactory, $this->featureDetector);
        $this->iterableSpyFactory = new IterableSpyFactory($this->callEventFactory);
        $this->subject = new SpyData(
            $this->callback,
            $this->label,
            $this->callFactory,
            $this->invoker,
            $this->generatorSpyFactory,
            $this->iterableSpyFactory
        );

        $this->callA = $this->callFactory->create();
        $this->callB = $this->callFactory->create();
        $this->calls = [$this->callA, $this->callB];

        $this->callFactory->reset();
    }

    public function testInvokeWithWithGeneratorSpy()
    {
        $this->callback = function () {
            foreach (func_get_args() as $argument) {
                yield strtoupper($argument);
            }
        };
        $spy = new SpyData(
            $this->callback,
            '',
            $this->callFactory,
            $this->invoker,
            $this->generatorSpyFactory,
            $this->iterableSpyFactory
        );
        foreach ($spy->invoke('a', 'b') as $value) {
        }
        foreach ($spy->invoke('c') as $value) {
        }
        $this->callFactory->reset();
        $generatorA = call_user_func($this->callback, 'a', 'b');
        $generatorB = call_user_func($this->callback, 'c');
        $expectedCallA =
            $this->callFactory->create($this->callEventFactory->createCalled($spy, Arguments::create('a', 'b')));
        $generatorSpyA = $this->generatorSpyFactory->create($expectedCallA, $generatorA);
        $expectedCallA->setResponseEvent($this->callEventFactory->createReturned($generatorA));
        iterator_to_array($generatorSpyA);
        $expectedCallB =
            $this->callFactory->create($this->callEventFactory->createCalled($spy, Arguments::create('c')));
        $generatorSpyB = $this->generatorSpyFactory->create($expectedCallB, $generatorB);
        $expectedCallB->setResponseEvent($this->callEventFactory->createReturned($generatorB));
        iterator_to_array($generatorSpyB);
        $expected = [$expectedCallA, $expectedCallB];

        $this->assertEquals($expected, $spy->allCalls());
    }

    public function testInvokeWithGeneratorSpyDoubleWrap()
    {
        $this->callback = function ($a) {
            return $a;
        };
        $spy = new SpyData(
            $this->callback,
            '',
            $this->callFactory,
            $this->invoker,
            $this->generatorSpyFactory,
            $this->iterableSpyFactory
        );
        $function = function () {
            return;
            yield;
        };
        $generator = $function();
        $generatorSpyA = $spy->invoke($generator);
        $generatorSpyB = $spy->invoke($generatorSpyA);

        $this->assertInstanceOf(Generator::class, $generatorSpyA);
        $this->assertInstanceOf(Generator::class, $generatorSpyB);
        $this->assertNotSame($generatorSpyA, $generatorSpyB);
    }
}
