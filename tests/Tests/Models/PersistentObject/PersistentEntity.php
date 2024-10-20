<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\PersistentObject;

use Doctrine\Common\Persistence\PersistentObject;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity]
class PersistentEntity extends PersistentObject
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    protected $id;

    /** @var string */
    #[Column(type: 'string', length: 255)]
    protected $name;

    /** @var PersistentEntity */
    #[ManyToOne(targetEntity: 'PersistentEntity')]
    protected $parent;
}
