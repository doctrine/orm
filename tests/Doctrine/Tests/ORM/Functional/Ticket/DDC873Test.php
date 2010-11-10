<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use	Doctrine\ORM\Mapping\ClassMetadata;

require_once __DIR__ . '/../../../TestInit.php';

class DDC873 extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        parent::setUp();
    }
    
    /**
     * @group DDC-873
     */
    public function testCompositeKeyWithVersion ()
    {
    	$cm = new ClassMetadata(__NAMESPACE__ . '\DDC873CompositeKeyWithVersion');
    	
    	$this->setExpectedException(
            'Doctrine\ORM\Mapping\MappingException',
    		"If you wish to use @Version, you may not use @Id at the same time in '".__NAMESPACE__ . '\DDC873CompositeKeyWithVersion'."' on field 'version'."

        );

        $metadata = $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC873CompositeKeyWithVersion');
    	
        
    }
}

/**
 * @Entity
 * @Table(name="ddc873_composite_key")
 */
class DDC873CompositeKeyWithVersion
{
    /**
     * @Column(type="integer")
     * @Id @GeneratedValue
     */
    public $id;
    
    /**
     * @Column(type="integer")
     * @Id
     * @Version
     */
    public $version;

}