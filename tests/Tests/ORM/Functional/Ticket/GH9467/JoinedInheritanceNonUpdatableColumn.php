<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH9467;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'joined_inheritance_non_updatable_column')]
class JoinedInheritanceNonUpdatableColumn extends JoinedInheritanceRoot
{
    /** @var string */
    #[Column(type: 'string', insertable: true, updatable: false, options: ['default' => 'dbDefault'], generated: 'ALWAYS')]
    public $nonUpdatableContent;
}
