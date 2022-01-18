<?php

declare(strict_types=1);

namespace Doctrine\Tests\DbalTypes;

class CustomIdObject
{
    public string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
