<?php

/*
 * This file is part of the Phony package.
 *
 * Copyright © 2016 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Phony\Matcher;

/**
 * Verifies argument lists against matcher lists.
 */
class MatcherVerifier
{
    /**
     * Get the static instance of this verifier.
     *
     * @return MatcherVerifier The static verifier.
     */
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Verify that the supplied arguments match the supplied matchers.
     *
     * @param array<Matcher> $matchers  The matchers.
     * @param array          $arguments The arguments.
     *
     * @return bool True if the arguments match.
     */
    public function matches(array $matchers, array $arguments)
    {
        $pair = each($arguments);

        foreach ($matchers as $matcher) {
            if ($matcher instanceof WildcardMatcher) {
                $matchCount = 0;
                $innerMatcher = $matcher->matcher();

                while ($pair && $innerMatcher->matches($pair[1])) {
                    ++$matchCount;
                    $pair = each($arguments);
                }

                if ($matchCount < $matcher->minimumArguments()) {
                    return false;
                }

                if (
                    null !== $matcher->maximumArguments() &&
                    $matchCount > $matcher->maximumArguments()
                ) {
                    return false;
                }

                continue;
            } elseif (empty($pair)) {
                return false;
            } elseif (!$matcher->matches($pair[1])) {
                return false;
            }

            $pair = each($arguments);
        }

        return false === $pair;
    }

    private static $instance;
}