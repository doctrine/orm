<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-1738
 */
class MultipleIdGeneratorTest extends OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\MultipleIdGeneratorEntity')
        ));
    }

    public function testMultipleIdGenerator()
    {
        $entity = new MultipleIdGeneratorEntity();
        $entity->setId(1);
        $entity->setSecondId(rand(1, PHP_INT_MAX));

        $this->_em->persist($entity);

        $this->assertNotNull($entity->getId());
        $this->assertNotNull($entity->getSecondId());
    }
}

/**
 * @Entity
 */
class MultipleIdGeneratorEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="NONE")
     */
    private $secondId;

    /**
     * Set id.
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set second id.
     *
     * @param integer $secondId
     */
    public function setSecondId($secondId)
    {
        $this->secondId = $secondId;
    }

    /**
     * Get second id.
     *
     * @return integer
     */
    public function getSecondId()
    {
        return $this->secondId;
    }
}
