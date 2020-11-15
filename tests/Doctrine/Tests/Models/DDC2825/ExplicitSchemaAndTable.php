<?php

namespace Doctrine\Tests\Models\DDC2825;

use Doctrine\ORM\Mapping as ORM;

/** @Entity @Table(name="explicit_table", schema="explicit_schema") */
#[ORM\Entity, ORM\Table(name: "explicit_table", schema: "explicit_schema")]
class ExplicitSchemaAndTable
{
    /** @Id @Column(type="integer") @GeneratedValue(strategy="AUTO") */
    #[ORM\Id, ORM\Column(type: "integer"), ORM\GeneratedValue(strategy: "AUTO")]
    public $id;
}
