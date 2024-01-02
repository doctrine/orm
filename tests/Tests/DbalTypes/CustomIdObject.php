<?php

declare(strict_types=1);

namespace Doctrine\Tests\DbalTypes;

class CustomIdObject
{
    /** @var string */
    public $id;

    public function __construct(string $id)
    {
        $this->id = (string) $id;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
