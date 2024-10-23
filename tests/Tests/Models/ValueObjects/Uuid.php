<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueObjects;

class Uuid
{
    /** @var string */
    public $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }
}
