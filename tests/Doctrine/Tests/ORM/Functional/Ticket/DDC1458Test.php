<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsGroup;

require_once __DIR__ . '/../../../TestInit.php';

class DDC1258Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\TestEntity'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\TestAdditionalEntity')
        ));
    }

    public function testIssue()
    {
        $testEntity = new TestEntity();
        $testEntity->setValue(3);
        $testEntity->setAdditional(new TestAdditionalEntity());
        $this->_em->persist($testEntity);
        $this->_em->flush();
        $this->_em->clear();

        // So here the value is 3
        $this->assertEquals(3, $testEntity->getValue());

        $test = $this->_em->getRepository(__NAMESPACE__ . '\TestEntity')->find(1);

        // New value is set
        $test->setValue(5);

        // So here the value is 5
        $this->assertEquals(5, $test->getValue());

        // Get the additional entity
        $additional = $test->getAdditional();

        // Still 5..
        $this->assertEquals(5, $test->getValue());

        // Force the proxy to load
        $additional->getBool();

        // The value should still be 5
        $this->assertEquals(5, $test->getValue());
    }
}


/**
 * @Entity
 */
class TestEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;
    /**
     * @Column(type="integer")
     */
    protected $value;
    /**
     * @OneToOne(targetEntity="TestAdditionalEntity", inversedBy="entity", orphanRemoval=true, cascade={"persist", "remove"})
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
 * @Entity
 */
class TestAdditionalEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;
    /**
     * @OneToOne(targetEntity="TestEntity", mappedBy="additional")
     */
    protected $entity;
    /**
     * @Column(type="boolean")
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
