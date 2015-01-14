<?php

namespace Doctrine\Tests\Models\DDC2825;

/**
 * @Entity
 * @Table(name="mytable2", schema="myschema")
 */
class ExplicitSchemaAndTable
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id @Column()
     */
    public $id;
}
