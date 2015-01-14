<?php

namespace Doctrine\Tests\Models\DDC2825;

/**
 * Quoted column name to check that sequence names are
 * correctly handled
 *
 * @Entity @Table(name="implicit_schema.implicit_table")
 */
class SchemaAndTableInTableName
{
    const CLASSNAME = __CLASS__;

    /** @Id @Column(type="integer") @GeneratedValue(strategy="AUTO") */
    public $id;
}