<?php

namespace Doctrine\Tests\Models\DDC2825;

/**
 * Quoted column name to check that sequence names are
 * correctly handled
 *
 * @Entity
 * @Table(name="myschema.mytable")
 */
class SchemaAndTableInTableName
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id @Column()
     */
    public $id;
}