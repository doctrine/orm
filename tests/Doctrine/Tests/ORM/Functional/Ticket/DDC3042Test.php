<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-3042
 */
class DDC3042Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(DDC3042Foo::class),
            $this->em->getClassMetadata(DDC3042Bar::class),
            ]
        );
    }

    public function testSQLGenerationDoesNotProvokeAliasCollisions()
    {
        self::assertStringNotMatchesFormat(
            '%sfield11%sfield11%s',
            $this
                ->em
                ->createQuery(
                    'SELECT f, b FROM ' . __NAMESPACE__ . '\DDC3042Foo f JOIN ' . __NAMESPACE__ . '\DDC3042Bar b WITH 1 = 1'
                )
                ->getSQL()
        );
    }
}

/**
 * @ORM\Entity
 */
class DDC3042Foo
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $field;
    /** @ORM\Column(type="integer") */
    public $field1;
    /** @ORM\Column(type="integer") */
    public $field2;
    /** @ORM\Column(type="integer") */
    public $field3;
    /** @ORM\Column(type="integer") */
    public $field4;
    /** @ORM\Column(type="integer") */
    public $field5;
    /** @ORM\Column(type="integer") */
    public $field6;
    /** @ORM\Column(type="integer") */
    public $field7;
    /** @ORM\Column(type="integer") */
    public $field8;
    /** @ORM\Column(type="integer") */
    public $field9;
    /** @ORM\Column(type="integer") */
    public $field10;
}

/**
 * @ORM\Entity
 */
class DDC3042Bar
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $field;
}
