<?php

declare(strict_types=1);

namespace BafS\PsalmTypecov\Report;

/**
 * @readonly
 * @psalm-immutable
 */
final class Thresholds
{
    private function __construct(public int $lowUpperBound, public int $highLowerBound)
    {
    }

    /**
     * @throws \InvalidArgumentException
     */
    public static function from(int $lowUpperBound, int $highLowerBound): self
    {
        if ($lowUpperBound > $highLowerBound) {
            throw new \InvalidArgumentException('$lowUpperBound must be smaller than $highLowerBound.');
        }

        return new self($lowUpperBound, $highLowerBound);
    }
}
