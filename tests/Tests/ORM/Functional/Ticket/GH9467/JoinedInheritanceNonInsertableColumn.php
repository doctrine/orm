<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH9467;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'joined_inheritance_non_insertable_column')]
class JoinedInheritanceNonInsertableColumn extends JoinedInheritanceRoot
{
    /** @var string */
    #[Column(type: 'string', insertable: false, updatable: true, options: ['default' => 'dbDefault'], generated: 'ALWAYS')]
    public $nonInsertableContent;
}
