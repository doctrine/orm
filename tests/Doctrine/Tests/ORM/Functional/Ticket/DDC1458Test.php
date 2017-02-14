<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC1258Test extends OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(TestEntity::class),
            $this->em->getClassMetadata(TestAdditionalEntity::class)
            ]
        );
    }

    public function testIssue()
    {
        $testEntity = new TestEntity();
        $testEntity->setValue(3);
        $testEntity->setAdditional(new TestAdditionalEntity());
        $this->em->persist($testEntity);
        $this->em->flush();
        $this->em->clear();

        // So here the value is 3
        self::assertEquals(3, $testEntity->getValue());

        $test = $this->em->getRepository(TestEntity::class)->find(1);

        // New value is set
        $test->setValue(5);

        // So here the value is 5
        self::assertEquals(5, $test->getValue());

        // Get the additional entity
        $additional = $test->getAdditional();

        // Still 5..
        self::assertEquals(5, $test->getValue());

        // Force the proxy to load
        $additional->getBool();

        // The value should still be 5
        self::assertEquals(5, $test->getValue());
    }
}


/**
 * @ORM\Entity
 */
class TestEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    /**
     * @ORM\Column(type="integer")
     */
    protected $value;
    /**
     * @ORM\OneToOne(targetEntity="TestAdditionalEntity", inversedBy="entity", orphanRemoval=true, cascade={"persist", "remove"})
     */
    protected $additional;

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getAdditional()
    {
        return $this->additional;
    }

    public function setAdditional($additional)
    {
        $this->additional = $additional;
    }
}
/**
 * @ORM\Entity
 */
class TestAdditionalEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    /**
     * @ORM\OneToOne(targetEntity="TestEntity", mappedBy="additional")
     */
    protected $entity;
    /**
     * @ORM\Column(type="boolean")
     */
    protected $bool;

    public function __construct()
    {
        $this->bool = false;
    }

    public function getBool()
    {
        return $this->bool;
    }

    public function setBool($bool)
    {
        $this->bool = $bool;
    }
}
