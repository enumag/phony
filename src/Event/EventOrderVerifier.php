<?php

declare(strict_types=1);

namespace Eloquent\Phony\Event;

use Eloquent\Phony\Assertion\AssertionRecorder;
use Eloquent\Phony\Assertion\AssertionRenderer;
use Eloquent\Phony\Assertion\ExceptionAssertionRecorder;
use InvalidArgumentException;
use Throwable;

/**
 * Checks and asserts the order of events.
 */
class EventOrderVerifier
{
    /**
     * Get the static instance of this verifier.
     *
     * @return EventOrderVerifier The static verifier.
     */
    public static function instance(): self
    {
        if (!self::$instance) {
            self::$instance = new self(
                ExceptionAssertionRecorder::instance(),
                AssertionRenderer::instance()
            );
        }

        return self::$instance;
    }

    /**
     * Construct a new event order verifier.
     *
     * @param AssertionRecorder $assertionRecorder The assertion recorder to use.
     * @param AssertionRenderer $assertionRenderer The assertion renderer to use.
     */
    public function __construct(
        AssertionRecorder $assertionRecorder,
        AssertionRenderer $assertionRenderer
    ) {
        $this->assertionRecorder = $assertionRecorder;
        $this->assertionRenderer = $assertionRenderer;
    }

    /**
     * Checks if the supplied events happened in chronological order.
     *
     * @param Event|EventCollection ...$events The events.
     *
     * @return EventCollection|null     The result.
     * @throws InvalidArgumentException If invalid input is supplied.
     */
    public function checkInOrder(...$events): ?EventCollection
    {
        if (!count($events)) {
            return null;
        }

        $isMatch = true;
        $matchingEvents = [];
        $earliestEvent = null;

        foreach ($events as $event) {
            if ($event instanceof Event) {
                if (
                    !$earliestEvent ||
                    $event->sequenceNumber() > $earliestEvent->sequenceNumber()
                ) {
                    $matchingEvents[] = $earliestEvent = $event;

                    continue;
                }
            } elseif ($event instanceof EventCollection) {
                if (!$event->hasEvents()) {
                    throw new InvalidArgumentException(
                        'Cannot verify event order with empty results.'
                    );
                }

                foreach ($event->allEvents() as $subEvent) {
                    if (
                        !$earliestEvent ||
                        (
                            $subEvent->sequenceNumber() >
                            $earliestEvent->sequenceNumber()
                        )
                    ) {
                        $matchingEvents[] = $earliestEvent = $subEvent;

                        continue 2;
                    }
                }
            } else {
                throw new InvalidArgumentException(
                    sprintf(
                        'Cannot verify event order with supplied value %s.',
                        $this->assertionRenderer->renderValue($event)
                    )
                );
            }

            $isMatch = false;

            break;
        }

        if ($isMatch) {
            return $this->assertionRecorder->createSuccess($matchingEvents);
        }

        return null;
    }

    /**
     * Throws an exception unless the supplied events happened in chronological
     * order.
     *
     * @param Event|EventCollection ...$events The events.
     *
     * @return EventCollection|null     The result, or null if the assertion recorder does not throw exceptions.
     * @throws InvalidArgumentException If invalid input is supplied.
     * @throws Throwable                If the assertion fails, and the assertion recorder throws exceptions.
     */
    public function inOrder(...$events): ?EventCollection
    {
        if ($result = $this->checkInOrder(...$events)) {
            return $result;
        }

        return $this->assertionRecorder->createFailure(
            $this->assertionRenderer->renderInOrder(
                $this->expectedEvents($events),
                $this->mergeEvents($events)
            )
        );
    }

    /**
     * Checks that at least one event is supplied.
     *
     * @param Event|EventCollection ...$events The events.
     *
     * @return EventCollection|null     The result.
     * @throws InvalidArgumentException If invalid input is supplied.
     */
    public function checkAnyOrder(...$events): ?EventCollection
    {
        if (!count($events)) {
            return null;
        }

        return $this->assertionRecorder
            ->createSuccess($this->mergeEvents($events));
    }

    /**
     * Throws an exception unless at least one event is supplied.
     *
     * @param Event|EventCollection ...$events The events.
     *
     * @return EventCollection|null     The result, or null if the assertion recorder does not throw exceptions.
     * @throws InvalidArgumentException If invalid input is supplied.
     * @throws Throwable                If the assertion fails, and the assertion recorder throws exceptions.
     */
    public function anyOrder(...$events): ?EventCollection
    {
        if ($result = $this->checkAnyOrder(...$events)) {
            return $result;
        }

        return $this->assertionRecorder
            ->createFailure('Expected events. No events recorded.');
    }

    private function expectedEvents($events)
    {
        $expected = [];
        $earliestEvent = null;

        foreach ($events as $event) {
            if ($event instanceof Event) {
                $expected[] = $earliestEvent = $event;
            } else {
                if (!$event->hasEvents()) {
                    throw new InvalidArgumentException(
                        'Cannot verify event order with empty results.'
                    );
                }

                $subEvent = null;

                foreach ($event->allEvents() as $subEvent) {
                    if (
                        !$earliestEvent ||
                        (
                            $subEvent->sequenceNumber() >
                            $earliestEvent->sequenceNumber()
                        )
                    ) {
                        break;
                    }
                }

                $expected[] = $earliestEvent = $subEvent;
            }
        }

        return $expected;
    }

    private function mergeEvents($events)
    {
        $merged = [];

        foreach ($events as $event) {
            if ($event instanceof Event) {
                $merged[$event->sequenceNumber()] = $event;
            } else {
                foreach ($event->allEvents() as $subEvent) {
                    $merged[$subEvent->sequenceNumber()] = $subEvent;
                }
            }
        }

        ksort($merged);

        return array_values($merged);
    }

    private static $instance;
    private $assertionRecorder;
    private $assertionRenderer;
}
