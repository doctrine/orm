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
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('DDC-2359')]
class DDC2359Test extends TestCase
{
    /**
     * Verifies that {@see \Doctrine\ORM\Mapping\ClassMetadataFactory::wakeupReflection} is
     * not called twice when loading metadata from a driver
     */
    public function testIssue(): void
    {
        $stubDriver    = $this->createStub(MappingDriver::class);
        $stubMetadata  = $this->createStub(ClassMetadata::class);
        $entityManager = $this->createStub(EntityManager::class);

        $metadataFactory = $this->getMockBuilder(ClassMetadataFactory::class)
            ->onlyMethods(['newClassMetadataInstance', 'wakeupReflection'])
            ->getMock();

        $configuration = $this->getMockBuilder(Configuration::class)
            ->onlyMethods(['getMetadataDriverImpl'])
            ->getMock();

        $connection = $this->createStub(Connection::class);

        $configuration
            ->method('getMetadataDriverImpl')
            ->willReturn($stubDriver);

        $entityManager->method('getConfiguration')->willReturn($configuration);
        $entityManager->method('getConnection')->willReturn($connection);
        $entityManager
            ->method('getEventManager')
            ->willReturn(new EventManager());

        $metadataFactory->method('newClassMetadataInstance')->willReturn($stubMetadata);
        $metadataFactory->expects(self::once())->method('wakeupReflection');

        $metadataFactory->setEntityManager($entityManager);

        $stubMetadata->method('getName')->willReturn(DDC2359Foo::class);

        self::assertSame($stubMetadata, $metadataFactory->getMetadataFor(DDC2359Foo::class));
    }
}

#[Entity]
class DDC2359Foo
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;
}
