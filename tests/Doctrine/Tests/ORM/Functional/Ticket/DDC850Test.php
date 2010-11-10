<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\ClassMetadata;

require_once __DIR__ . '/../../../TestInit.php';

class DDC850Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        parent::setUp();
    }
    
    /**
     * @group DDC-850
     */
    public function testBrokenCascadeAnnotationException()
    {
    	$cm = new ClassMetadata(__NAMESPACE__ . '\DDC850BrokenCascade');
    	
    	$this->setExpectedException(
            'Doctrine\ORM\Mapping\MappingException',
            "The cascade argument passed to '".__NAMESPACE__ . '\DDC850BrokenCascade'."' on field 'brokenCascadeTarget' is invalid. The argument you passed was 'ALL', which is not an array. Try {'ALL'} instead, and consult the manual about the parameters for cascade=."
        );

        $metadata = $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC850BrokenCascade');
    	
        
    }
}

/**
 * @Entity
 * @Table(name="ddc850_broken_cascade")
 */
class DDC850BrokenCascade
{
    /**
     * @Column(type="integer")
     * @Id @GeneratedValue
     */
    public $id;
    
	/**
	 * @ManyToOne(targetEntity="DDC850BrokenCascadeTarget", cascade="ALL", fetch="EAGER")
	 */
	private $brokenCascadeTarget;

}

/**
 * @Entity
 * @Table(name="dcc850_broken_cascade_target")
 */
class DDC850BrokenCascadeTarget
{
    /**
     * @Column(type="integer")
     * @Id @GeneratedValue
     */
    public $id;
    
}