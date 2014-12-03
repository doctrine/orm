<?php

namespace Doctrine\Tests\Models\NullDefault;

/**
 * @Entity
 * @Table(name="null-default")
 */
class NullDefaultColumn
{

    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @Column(name="`null-default`",nullable=true,options={"default":NULL})
     */
    public $nullDefault;
}
