<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\InheritsIndexFromMappedSuperclass;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping\Table;

/**
 * @MappedSuperclass
 * @Table(indexes={@Index(columns={"name"}), @Index(name="composite_idx", columns={"name", "label"})})
 */
abstract class Pet
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     * @Column(type="string")
     */
    public $name;

    /**
     * @var string
     * @Column(type="string")
     */
    public $label;
}
