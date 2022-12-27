<?php

declare(strict_types=1);

namespace BafS\PsalmTypecov\Report;

interface ReportInterface
{
    /** @psalm-param iterable<string, array{int, int}> $result */
    public function generate(iterable $result): void;
}
