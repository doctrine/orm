<?php

namespace Doctrine\Tests\Models\DDC2825;

/** @Entity @Table(name="explicit_table", schema="explicit_schema") */
class ExplicitSchemaAndTable
{
    const CLASSNAME = __CLASS__;

    /** @Id @Column(type="integer") @GeneratedValue(strategy="AUTO") */
    public $id;
}
