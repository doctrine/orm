<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC6573;

final class DDC6573Money
{
    public function __construct(
        private readonly int $amount,
        private readonly DDC6573Currency $currency,
    ) {
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): DDC6573Currency
    {
        return $this->currency;
    }
}
