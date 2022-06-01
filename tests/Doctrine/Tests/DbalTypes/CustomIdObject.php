<?php

declare(strict_types=1);

namespace Doctrine\Tests\DbalTypes;

use Stringable;

class CustomIdObject implements Stringable
{
    public function __construct(public string $id)
    {
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
