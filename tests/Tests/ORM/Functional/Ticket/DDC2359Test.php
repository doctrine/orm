<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-2359')]
class DDC2359Test extends OrmTestCase
{
    /**
     * Verifies that {@see \Doctrine\ORM\Mapping\ClassMetadataFactory::wakeupReflection} is
     * not called twice when loading metadata from a driver
     */
    public function testIssue(): void
    {
        $mockDriver    = $this->createAttributeDriver();
        $mockMetadata  = new ClassMetadata(DDC2359Foo::class);
        $entityManager = $this->createMock(EntityManager::class);

        $metadataFactory = $this->getMockBuilder(ClassMetadataFactory::class)
            ->onlyMethods(['newClassMetadataInstance', 'wakeupReflection'])
            ->getMock();

        $configuration = $this->getMockBuilder(Configuration::class)
            ->onlyMethods(['getMetadataDriverImpl'])
            ->getMock();

        $connection = $this->createMock(Connection::class);

        $configuration
            ->method('getMetadataDriverImpl')
            ->will(self::returnValue($mockDriver));

        $entityManager->method('getConfiguration')->will(self::returnValue($configuration));
        $entityManager->method('getConnection')->will(self::returnValue($connection));
        $entityManager
            ->method('getEventManager')
            ->will(self::returnValue($this->createMock(EventManager::class)));

        $metadataFactory->method('newClassMetadataInstance')->will(self::returnValue($mockMetadata));
        $metadataFactory->expects(self::once())->method('wakeupReflection');

        $metadataFactory->setEntityManager($entityManager);

        self::assertSame($mockMetadata, $metadataFactory->getMetadataFor(DDC2359Foo::class));
    }
}

#[Entity]
class DDC2359Foo
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public int $id;
}
