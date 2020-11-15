<?php

namespace Doctrine\Tests\Models\DDC2825;

use Doctrine\ORM\Mapping as ORM;

/**
 * Quoted column name to check that sequence names are
 * correctly handled
 *
 * @Entity @Table(name="implicit_schema.implicit_table")
 */
#[ORM\Entity, ORM\Table(name: "implicit_schema.implicit_table")]
class SchemaAndTableInTableName
{
    /** @Id @Column(type="integer") @GeneratedValue(strategy="AUTO") */
    #[ORM\Id, ORM\Column(type: "integer"), ORM\GeneratedValue(strategy: "AUTO")]
    public $id;
}
