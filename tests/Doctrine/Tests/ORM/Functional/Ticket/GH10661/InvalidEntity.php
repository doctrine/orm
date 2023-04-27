<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH10661;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;

/** @Entity */
class InvalidEntity
{
    /**
     * @var int
     * @Id
     * @Column
     */
    protected $key;

    /**
     * @Column(type="decimal")
     */
    protected float $property1;
}
