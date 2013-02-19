<?php

namespace Doctrine\Tests\ORM\Functional;

class ValueObjectsTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
    }
}

/**
 * @Entity
 */
class DDC93Person
{
    /** @Id @GeneratedValue @Column(type="integer") */
    public $id;

    /** @Column(type="string") */
    public $name;

    /** @Embedded */
    public $address;
}
