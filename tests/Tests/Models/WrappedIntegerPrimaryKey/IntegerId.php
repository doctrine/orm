<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\WrappedIntegerPrimaryKey;

class IntegerId
{
    public function __construct(private int $value)
    {
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
