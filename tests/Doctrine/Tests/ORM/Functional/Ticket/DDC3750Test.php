<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-3750
 */
class DDC3750Test extends OrmFunctionalTestCase
{
    /** @var  EntityGenerator */
    protected $_generator;
    protected $_namespace = '\Doctrine\Tests\ORM\Functional\Ticket';
    protected function setUp()
    {
        parent::setUp();
        $this->_generator = new EntityGenerator();
    }

    public function testHasMethodExtendedFromAbstract()
    {
        $this->_generator->setClassToExtend($this->_namespace . '\DDC3750TestAbstractClass');

        $metadata = new ClassMetadataInfo($this->_namespace . '\EntityType');
        $metadata->namespace = $this->_namespace;

        $metadata->table['name'] = 'entity_type';

        $this->assertFalse($this->invokeMethod($this->_generator, 'hasMethod', array('getId', $metadata)));
    }

    /**
     * @param $object
     * @param string $methodName
     * @param array $args
     * @return \ReflectionMethod
     */
    protected function invokeMethod($object, $methodName, array $args = array())
    {
        $class = new \ReflectionObject($object);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }
}


abstract class DDC3750TestAbstractClass
{
    abstract public function getId();
}