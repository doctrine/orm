<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * @group DDC-2084
 */
class DDC2084Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(__NAMESPACE__ . '\DDC2084\MyEntity1'),
                $this->em->getClassMetadata(__NAMESPACE__ . '\DDC2084\MyEntity2'),
                ]
            );
        } catch (\Exception $exc) {
        }
    }

    public function loadFixture()
    {
        $e2 = new DDC2084\MyEntity2('Foo');
        $e1 = new DDC2084\MyEntity1($e2);

        $this->em->persist($e2);
        $this->em->flush();

        $this->em->persist($e1);
        $this->em->flush();

        $this->em->clear();

        return $e1;
    }

    public function testIssue()
    {
        $e1 = $this->loadFixture();
        $e2 = $e1->getMyEntity2();
        $e  = $this->em->find(__NAMESPACE__ . '\DDC2084\MyEntity1', $e2);

        self::assertInstanceOf(__NAMESPACE__ . '\DDC2084\MyEntity1', $e);
        self::assertInstanceOf(__NAMESPACE__ . '\DDC2084\MyEntity2', $e->getMyEntity2());
        self::assertEquals('Foo', $e->getMyEntity2()->getValue());
    }

    /**
     * @expectedException \Doctrine\ORM\ORMInvalidArgumentException
     * @expectedExceptionMessage  Binding entities to query parameters only allowed for entities that have an identifier.
     */
    public function testinvalidIdentifierBindingEntityException()
    {
        $this->em->find(__NAMESPACE__ . '\DDC2084\MyEntity1', new DDC2084\MyEntity2('Foo'));
    }
}

namespace Doctrine\Tests\ORM\Functional\Ticket\DDC2084;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="DDC2084_ENTITY1")
 */
class MyEntity1
{
    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="MyEntity2")
     * @ORM\JoinColumn(name="entity2_id", referencedColumnName="id", nullable=false)
     */
    private $entity2;

    public function __construct(MyEntity2 $myEntity2)
    {
        $this->entity2 = $myEntity2;
    }

    public function setMyEntity2(MyEntity2 $myEntity2)
    {
        $this->entity2 = $myEntity2;
    }

    public function getMyEntity2()
    {
        return $this->entity2;
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="DDC2084_ENTITY2")
 */
class MyEntity2
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column
     */
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }
}
