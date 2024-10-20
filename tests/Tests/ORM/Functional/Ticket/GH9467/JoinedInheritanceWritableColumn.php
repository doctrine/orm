<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH9467;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'joined_inheritance_writable_column')]
class JoinedInheritanceWritableColumn extends JoinedInheritanceRoot
{
    /** @var string */
    #[Column(type: 'string', insertable: true, updatable: true, options: ['default' => 'dbDefault'], generated: 'ALWAYS')]
    public $writableContent;
}
