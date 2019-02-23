<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use ArrayIterator;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\Driver\MappingDriver;
use Doctrine\Tests\DoctrineTestCase;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * @group DDC-2359
 */
class DDC2359Test extends DoctrineTestCase
{
    /**
     * Verifies that {@see \Doctrine\ORM\Mapping\ClassMetadataFactory::wakeupReflection} is
     * not called when loading metadata from a driver
     */
    public function testIssue() : void
    {
        $mockDriver    = $this->createMock(MappingDriver::class);
        $mockMetadata  = $this->createMock(ClassMetadata::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        /** @var ClassMetadataFactory|PHPUnit_Framework_MockObject_MockObject $metadataFactory */
        $metadataFactory = $this->getMockBuilder(ClassMetadataFactory::class)
                                ->setMethods(['doLoadMetadata', 'wakeupReflection'])
                                ->getMock();

        $configuration = $this->getMockBuilder(Configuration::class)
                              ->setMethods(['getMetadataDriverImpl'])
                              ->getMock();

        $connection = $this->createMock(Connection::class);

        $configuration
            ->expects($this->any())
            ->method('getMetadataDriverImpl')
            ->will($this->returnValue($mockDriver));

        $mockMetadata
            ->expects($this->any())
            ->method('getDeclaredPropertiesIterator')
            ->will($this->returnValue(new ArrayIterator([])));

        $entityManager
            ->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue($configuration));

        $entityManager
            ->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $entityManager
            ->expects($this->any())
            ->method('getEventManager')
            ->will($this->returnValue($this->createMock(EventManager::class)));

        $metadataFactory
            ->expects($this->any())
            ->method('doLoadMetadata')
            ->will($this->returnValue($mockMetadata));

        $metadataFactory
            ->expects($this->never())
            ->method('wakeupReflection');

        $metadataFactory->setEntityManager($entityManager);

        self::assertSame($mockMetadata, $metadataFactory->getMetadataFor(DDC2359Foo::class));
    }
}

/** @ORM\Entity */
class DDC2359Foo
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
}
