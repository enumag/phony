<?php

/*
 * This file is part of the Phony package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Phony\Integration\Phpunit;

use Eloquent\Phony\Assertion\AssertionRecorderInterface;
use Exception;
use PHPUnit_Framework_Assert;
use PHPUnit_Framework_ExpectationFailedException;

/**
 * An assertion recorder that uses PHPUnit_Framework_Assert::assertThat().
 *
 * @see PHPUnit_Framework_Assert::assertThat()
 */
class PhpunitAssertionRecorder implements AssertionRecorderInterface
{
    /**
     * Get the static instance of this recorder.
     *
     * @return AssertionRecorderInterface The static recorder.
     */
    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Record that a successful assertion occurred.
     */
    public function recordSuccess()
    {
        PHPUnit_Framework_Assert::assertThat(
            true,
            PHPUnit_Framework_Assert::isTrue()
        );
    }

    /**
     * Create a new assertion failure exception.
     *
     * @param string $description The failure description.
     *
     * @return Exception The appropriate assertion failure exception.
     */
    public function createFailure($description)
    {
        return new PHPUnit_Framework_ExpectationFailedException($description);
    } // @codeCoverageIgnore

    private static $instance;
}