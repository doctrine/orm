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

#[Entity]
#[Table(name: 'joined_inheritance_root')]
#[InheritanceType('JOINED')]
#[DiscriminatorColumn(name: 'discr', type: 'string')]
#[DiscriminatorMap([
    'child' => JoinedInheritanceChild::class,
    'writable' => JoinedInheritanceWritableColumn::class,
    'nonWritable' => JoinedInheritanceNonWritableColumn::class,
    'nonInsertable' => JoinedInheritanceNonInsertableColumn::class,
    'nonUpdatable' => JoinedInheritanceNonUpdatableColumn::class,
])]
class JoinedInheritanceRoot
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public $id;

    /** @var string */
    #[Column(type: 'string')]
    public $rootField = '';

    /** @var string */
    #[Column(type: 'string', insertable: true, updatable: true, options: ['default' => 'dbDefault'], generated: 'ALWAYS')]
    public $rootWritableContent = '';

    /** @var string */
    #[Column(type: 'string', insertable: false, updatable: false, options: ['default' => 'dbDefault'], generated: 'ALWAYS')]
    public $rootNonWritableContent;

    /** @var string */
    #[Column(type: 'string', insertable: false, updatable: true, options: ['default' => 'dbDefault'], generated: 'ALWAYS')]
    public $rootNonInsertableContent;

    /** @var string */
    #[Column(type: 'string', insertable: true, updatable: false, options: ['default' => 'dbDefault'], generated: 'ALWAYS')]
    public $rootNonUpdatableContent = '';
}
