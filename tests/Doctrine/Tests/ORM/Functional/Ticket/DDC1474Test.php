<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1474
 */
class DDC1474Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1474Entity'),
            ));

            $this->loadFixtures();
        } catch (\Exception $exc) {
            
        }
    }

    public function testTicket()
    {
        $query  = $this->_em->createQuery('SELECT - e.value AS value, e.id FROM ' . __NAMESPACE__ . '\DDC1474Entity e');
        $this->assertEquals('SELECT -d0_.value AS sclr0, d0_.id AS id1 FROM DDC1474Entity d0_', $query->getSQL());
        $result = $query->getResult();
        
        $this->assertEquals(2, sizeof($result));
        
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals(2, $result[1]['id']);
        
        $this->assertEquals(-10, $result[0]['value']);
        $this->assertEquals(20, $result[1]['value']);
        
        
        
        
        
        $query  = $this->_em->createQuery('SELECT e.id, + e.value AS value FROM ' . __NAMESPACE__ . '\DDC1474Entity e');
        $this->assertEquals('SELECT d0_.id AS id0, +d0_.value AS sclr1 FROM DDC1474Entity d0_', $query->getSQL());
        $result = $query->getResult();

        $this->assertEquals(2, sizeof($result));
        
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals(2, $result[1]['id']);
        
        $this->assertEquals(10, $result[0]['value']);
        $this->assertEquals(-20, $result[1]['value']);
    }

    public function loadFixtures()
    {
        $e1 = new DDC1474Entity(10);
        $e2 = new DDC1474Entity(-20);
        
        $this->_em->persist($e1);
        $this->_em->persist($e2);
        
        $this->_em->flush();
    }

}

/**
 * @Entity
 */
class DDC1474Entity
{

    /**
     * @Id 
     * @Column(type="integer")
     * @GeneratedValue()
     */
    protected $id;

    /**
     * @column(type="float") 
     */
    private $value;

    /**
     * @param string $float 
     */
    public function __construct($float)
    {
        $this->value = $float;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return float 
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param float $value 
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

}
