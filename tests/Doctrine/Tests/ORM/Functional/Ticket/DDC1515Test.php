<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;

/**
 * @group DDC-1515
 */
class DDC1515Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(DDC1515Foo::class),
            $this->em->getClassMetadata(DDC1515Bar::class),
            ]
        );
    }

    public function testIssue()
    {
        $bar = new DDC1515Bar();
        $this->em->persist($bar);
        $this->em->flush();

        $foo = new DDC1515Foo();
        $foo->bar = $bar;
        $this->em->persist($foo);
        $this->em->flush();
        $this->em->clear();

        $bar = $this->em->find(DDC1515Bar::class, $bar->id);
        self::assertInstanceOf(DDC1515Foo::class, $bar->foo);
    }
}

/**
 * @ORM\Entity
 */
class DDC1515Foo
{
    /**
     * @ORM\OneToOne(targetEntity="DDC1515Bar", inversedBy="foo") @ORM\Id
     */
    public $bar;
}

/**
 * @ORM\Entity
 */
class DDC1515Bar
{
    /**
     * @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
     */
    public $id;

    /**
     * @ORM\OneToOne(targetEntity="DDC1515Foo", mappedBy="bar")
     */
    public $foo;
}
