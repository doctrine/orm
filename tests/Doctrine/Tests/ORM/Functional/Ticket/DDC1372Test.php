<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1372
 */
class DDC1372Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    protected function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1372FooBar'),
            ));
            $this->loadFixture();
        } catch (\Exception $e) {
            
        }
    }

    public function testTicket()
    {
        $this->markTestIncomplete();
        
        $dql    = 'SELECT f FROM '.__NAMESPACE__.'\DDC1372FooBar f WHERE f.foo = :foo AND f.bar IN (:bar)';
        $query  = $this->_em->createQuery($dql);

        $query->setParameters(array(
            'bar' => array(1, 2, 3),
            'foo' => 1,
        ));
        
        $parameters = $query->getParameters();
        $result     = $query->getResult();
        
        
        $this->assertEquals($result, array('bar'=>array(1,2,3),'foo'=>1));
    }

    private function loadFixture()
    {
        $f1 = new DDC1372FooBar(1,1);
        $f2 = new DDC1372FooBar(2,2);
        $f3 = new DDC1372FooBar(3,3);
        $f4 = new DDC1372FooBar(4,4);

        $this->_em->persist($f1);
        $this->_em->persist($f2);
        $this->_em->persist($f3);
        $this->_em->persist($f4);

        $this->_em->flush();
        $this->_em->clear();
    }

}

/**
 * @Entity
 */
class DDC1372FooBar
{

    /** @Id @GeneratedValue @Column(type="integer") */
    protected $id;

    /** @Column */
    protected $foo;

    /** @Column */
    protected $bar;

    public function __construct($foo,$bar)
    {
        $this->bar = $bar;
        $this->foo = $foo;
    }

}