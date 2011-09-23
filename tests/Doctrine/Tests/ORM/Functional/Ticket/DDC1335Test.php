<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use DateTime;

require_once __DIR__ . '/../../../TestInit.php';

class DDC1135Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        
        $classes = array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1135User')
        );
                
        try {
            $this->_schemaTool->dropSchema($classes);
            $this->_schemaTool->createSchema($classes);
        } catch(\Exception $e) {
            
        }
    }
    

    public function testTicket()
    {
        $this->markTestIncomplete();
        
        $builder = $this->_em->createQueryBuilder();
        $builder->select('u')->from('Doctrine\Tests\ORM\Functional\Ticket\DDC1135User', 'u', 'u.id');


        $sql = $builder->getQuery()->getSQL();

        $this->assertEquals('SELECT d0_.id AS id0, d0_.name AS name1 FROM DDC1135User INDEX BY d0_.id', $sql);
    }

}

/**
 * @Entity
 */
class DDC1135User
{

    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @Column(type="string", length=255)
     */
    protected $name;

}