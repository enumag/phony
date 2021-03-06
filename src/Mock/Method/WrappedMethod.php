<?php

declare(strict_types=1);

namespace Eloquent\Phony\Mock\Method;

use Eloquent\Phony\Invocation\WrappedInvocable;
use Eloquent\Phony\Mock\Handle\Handle;
use Eloquent\Phony\Mock\Mock;

/**
 * The interface implemented by wrapped methods.
 */
interface WrappedMethod extends WrappedInvocable
{
    /**
     * Get the name.
     *
     * @return string The name.
     */
    public function name(): string;

    /**
     * Get the handle.
     *
     * @return Handle The handle.
     */
    public function handle(): Handle;

    /**
     * Get the mock.
     *
     * @return Mock|null The mock.
     */
    public function mock(): ?Mock;
}
