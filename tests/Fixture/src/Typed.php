<?php

declare(strict_types=1);

namespace Fixture;

/** @psalm-pure */
function double(int $value): int
{
    $result = $value * 2;

    return $result;
}
