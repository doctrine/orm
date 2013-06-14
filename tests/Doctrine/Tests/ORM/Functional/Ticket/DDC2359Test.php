<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\ClassMetadataFactory;

/**
 * @group DDC-2359
 */
class DDC2359Test extends \PHPUnit_Framework_TestCase
{

    /**
     * Verifies that {@see \Doctrine\ORM\Mapping\ClassMetadataFactory::wakeupReflection} is
     * not called twice when loading metadata from a driver
     */
    public function testIssue()
    {
        $mockDriver      = $this->getMock('Doctrine\\Common\\Persistence\\Mapping\\Driver\\MappingDriver');
        $mockMetadata    = $this->getMock('Doctrine\\ORM\\Mapping\\ClassMetadata', array(), array(), '', false);
        $entityManager   = $this->getMock('Doctrine\\ORM\\EntityManager', array(), array(), '', false);

        /* @var $metadataFactory \Doctrine\ORM\Mapping\ClassMetadataFactory|\PHPUnit_Framework_MockObject_MockObject */
        $metadataFactory = $this->getMock(
            'Doctrine\\ORM\\Mapping\\ClassMetadataFactory',
            array('newClassMetadataInstance', 'wakeupReflection')
        );
        
        $configuration   = $this->getMock('Doctrine\\ORM\\Configuration', array('getMetadataDriverImpl'));
        $connection      = $this->getMock('Doctrine\\DBAL\\Connection', array(), array(), '', false);

        $configuration
            ->expects($this->any())
            ->method('getMetadataDriverImpl')
            ->will($this->returnValue($mockDriver));

        $entityManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($configuration));
        $entityManager->expects($this->any())->method('getConnection')->will($this->returnValue($connection));
        $entityManager
            ->expects($this->any())
            ->method('getEventManager')
            ->will($this->returnValue($this->getMock('Doctrine\\Common\\EventManager')));

        $metadataFactory->expects($this->any())->method('newClassMetadataInstance')->will($this->returnValue($mockMetadata));
        $metadataFactory->expects($this->once())->method('wakeupReflection');

        $metadataFactory->setEntityManager($entityManager);

        $this->assertSame($mockMetadata, $metadataFactory->getMetadataFor(__NAMESPACE__ . '\\DDC2359Foo'));
    }
}

/** @Entity */
class DDC2359Foo
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
}