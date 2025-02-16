<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Internal;

class Node
{
    /** @var string */
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
