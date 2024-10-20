<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC2825;

use Doctrine\ORM\Mapping as ORM;

/**
 * Quoted column name to check that sequence names are
 * correctly handled
 */
#[ORM\Entity]
#[ORM\Table(name: 'implicit_schema.implicit_table')]
class SchemaAndTableInTableName
{
    /** @var int */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public $id;
}
