<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC6573;

final class DDC6573Currency
{
    public function __construct(private readonly string $code)
    {
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
