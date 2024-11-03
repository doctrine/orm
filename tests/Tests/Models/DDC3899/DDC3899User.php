<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3899;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'dc3899_users')]
#[Entity]
class DDC3899User
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    public $id;

    /** @phpstan-var Collection<int, DDC3899Contract> */
    #[OneToMany(targetEntity: 'DDC3899Contract', mappedBy: 'user')]
    public $contracts;
}
