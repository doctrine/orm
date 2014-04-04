<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-3042
 */
class DDC3042Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC3042Foo'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC3042Bar'),
        ));
    }

    public function testSQLGenerationDoesNotProvokeAliasCollisions()
    {
        $this->assertStringNotMatchesFormat(
            '%sfield11%sfield11%s',
            $this
                ->_em
                ->createQuery(
                    'SELECT f, b FROM ' . __NAMESPACE__ . '\DDC3042Foo f JOIN ' . __NAMESPACE__ . '\DDC3042Bar b WITH 1 = 1'
                )
                ->getSQL()
        );
    }
}

/**
 * @Entity
 */
class DDC3042Foo
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $field;
    /** @Column(type="integer") */
    public $field1;
    /** @Column(type="integer") */
    public $field2;
    /** @Column(type="integer") */
    public $field3;
    /** @Column(type="integer") */
    public $field4;
    /** @Column(type="integer") */
    public $field5;
    /** @Column(type="integer") */
    public $field6;
    /** @Column(type="integer") */
    public $field7;
    /** @Column(type="integer") */
    public $field8;
    /** @Column(type="integer") */
    public $field9;
    /** @Column(type="integer") */
    public $field10;
}

/**
 * @Entity
 */
class DDC3042Bar
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $field;
}
