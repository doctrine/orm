<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\NonPublicSchemaJoins;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\Table;

/**
 * Doctrine\Tests\Models\NonPublicSchemaJoins\User
 */
#[Table(name: 'readers.user')]
#[Entity]
class User
{
    /** @var int */
    #[Column(type: 'integer')]
    #[Id]
    public $id;

    /** @var User[] */
    #[JoinTable(name: 'author_reader', schema: 'readers')]
    #[JoinColumn(name: 'author_id', referencedColumnName: 'id')]
    #[InverseJoinColumn(name: 'reader_id', referencedColumnName: 'id')]
    #[ManyToMany(targetEntity: 'Doctrine\Tests\Models\NonPublicSchemaJoins\User', inversedBy: 'authors')]
    public $readers;

    /** @var User[] */
    #[ManyToMany(targetEntity: 'Doctrine\Tests\Models\NonPublicSchemaJoins\User', mappedBy: 'readers')]
    public $authors;
}
