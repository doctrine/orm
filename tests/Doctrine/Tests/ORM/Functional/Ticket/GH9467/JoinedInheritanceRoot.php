<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH9467;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="joined_inheritance_root")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *      "writable" = "JoinedInheritanceWritableColumn",
 *      "nonWritable" = "JoinedInheritanceNonWritableColumn",
 *      "nonInsertable" = "JoinedInheritanceNonInsertableColumn",
 *      "nonUpdatable" = "JoinedInheritanceNonUpdatableColumn"
 * })
 */
#[Entity]
#[Table(name: 'joined_inheritance_root')]
#[InheritanceType('JOINED')]
#[DiscriminatorColumn(name: 'discr', type: 'string')]
#[DiscriminatorMap(['writable' => JoinedInheritanceWritableColumn::class, 'nonWritable' => JoinedInheritanceNonWritableColumn::class, 'nonInsertable' => JoinedInheritanceNonInsertableColumn::class, 'nonUpdatable' => JoinedInheritanceNonUpdatableColumn::class])]
class JoinedInheritanceRoot
{
    /**
     * @var int
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public $id;

    /**
     * @var string
     * @Column(type="string")
     */
    #[Column(type: 'string')]
    public $rootField = '';
}
