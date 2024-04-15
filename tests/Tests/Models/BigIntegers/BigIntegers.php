<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\BigIntegers;

use Doctrine\ORM\Mapping as ORM;

/** @ORM\Entity */
class BigIntegers
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    public ?int $id = null;

    /** @ORM\Column(type="bigint") */
    public int $one = 1;

    /** @ORM\Column(type="bigint") */
    public string $two = '2';

    /** @ORM\Column(type="bigint") */
    public float $three = 3.0;
}
