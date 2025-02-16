<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Quote;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Table(name: '`quote-group`')]
#[Entity]
class Group
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer', name: '`group-id`')]
    public $id;

    /** @phpstan-var Collection<int, User> */
    #[ManyToMany(targetEntity: 'User', mappedBy: 'groups')]
    public $users;

    public function __construct(
        #[Column(name: '`group-name`')]
        public string|null $name = null,
        #[ManyToOne(targetEntity: 'Group', cascade: ['persist'])]
        #[JoinColumn(name: '`parent-id`', referencedColumnName: '`group-id`')]
        public Group|null $parent = null,
    ) {
    }
}
