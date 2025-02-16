<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3346;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'ddc3346_articles')]
#[Entity]
class DDC3346Article
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    public $id;

    /** @var DDC3346Author */
    #[ManyToOne(targetEntity: 'DDC3346Author', inversedBy: 'articles')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    public $user;
}
