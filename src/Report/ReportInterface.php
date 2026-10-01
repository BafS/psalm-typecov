<?php

declare(strict_types=1);

namespace BafS\PsalmTypecov\Report;

/**
 * Psalm 7 purity tags (@psalm-mutable, @psalm-impure) are unknown to Psalm 5 and 6.
 *
 * @psalm-suppress MissingInterfaceImmutableAnnotation
 */
interface ReportInterface
{
    /**
     * @psalm-suppress MissingAbstractPureAnnotation
     * @psalm-param iterable<string, array{int, int}> $result
     */
    public function generate(iterable $result): void;
}
