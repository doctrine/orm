<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\InheritsIndexFromMappedSuperclass;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="cats",indexes={@Index(columns={"lives"}), @Index(name="composite_idx", columns={"name", "label", "lives"})})
 */
class Cat extends Pet
{
    /**
     * @var int
     * @Column(type="integer")
     */
    public $lives;
}
