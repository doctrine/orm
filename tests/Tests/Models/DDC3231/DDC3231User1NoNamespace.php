<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'no_namespace_users')]
#[Entity(repositoryClass: 'DDC3231User1NoNamespaceRepository')]
class DDC3231User1NoNamespace
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /** @var string */
    #[Column(type: 'string', length: 255)]
    protected $name;
}
