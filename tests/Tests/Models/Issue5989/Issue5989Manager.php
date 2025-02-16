<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Issue5989;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'issue5989_managers')]
#[Entity]
class Issue5989Manager extends Issue5989Person
{
    /** @var array */
    #[Column(type: 'simple_array', nullable: true)]
    public $tags;
}
