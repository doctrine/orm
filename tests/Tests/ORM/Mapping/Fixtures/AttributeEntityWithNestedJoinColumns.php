<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping\Fixtures;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class AttributeEntityWithNestedJoinColumns
{
    /** @var int */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public $id;

    /** @var Collection<AttributeEntityWithNestedJoinColumns> */
    #[ORM\ManyToMany(targetEntity: self::class)]
    #[ORM\JoinTable(
        name: 'assoc_table',
        joinColumns: new ORM\JoinColumn(name: 'assoz_id', referencedColumnName: 'assoz_id'),
        inverseJoinColumns: new ORM\JoinColumn(name: 'inverse_assoz_id', referencedColumnName: 'inverse_assoz_id'),
    )]
    public $assoc;
}
