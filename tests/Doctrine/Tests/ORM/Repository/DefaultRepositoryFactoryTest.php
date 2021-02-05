<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Repository;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Doctrine\Tests\Models\DDC753\DDC753DefaultRepository;
use Doctrine\Tests\Models\DDC869\DDC869PaymentRepository;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

use function assert;

/**
 * Tests for {@see \Doctrine\ORM\Repository\DefaultRepositoryFactory}
 *
 * @covers \Doctrine\ORM\Repository\DefaultRepositoryFactory
 */
class DefaultRepositoryFactoryTest extends TestCase
{
    /** @var EntityManagerInterface|PHPUnit_Framework_MockObject_MockObject */
    private $entityManager;

    /** @var Configuration|PHPUnit_Framework_MockObject_MockObject */
    private $configuration;

    /** @var DefaultRepositoryFactory */
    private $repositoryFactory;

    protected function setUp(): void
    {
        $this->configuration     = $this->createMock(Configuration::class);
        $this->entityManager     = $this->createEntityManager();
        $this->repositoryFactory = new DefaultRepositoryFactory();

        $this->configuration
            ->expects($this->any())
            ->method('getDefaultRepositoryClassName')
            ->will($this->returnValue(DDC869PaymentRepository::class));
    }

    public function testCreatesRepositoryFromDefaultRepositoryClass(): void
    {
        $this->entityManager
            ->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnCallback([$this, 'buildClassMetadata']));

        $this->assertInstanceOf(
            DDC869PaymentRepository::class,
            $this->repositoryFactory->getRepository($this->entityManager, self::class)
        );
    }

    public function testCreatedRepositoriesAreCached(): void
    {
        $this->entityManager
            ->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnCallback([$this, 'buildClassMetadata']));

        $this->assertSame(
            $this->repositoryFactory->getRepository($this->entityManager, self::class),
            $this->repositoryFactory->getRepository($this->entityManager, self::class)
        );
    }

    public function testCreatesRepositoryFromCustomClassMetadata(): void
    {
        $customMetadata                            = $this->buildClassMetadata(__DIR__);
        $customMetadata->customRepositoryClassName = DDC753DefaultRepository::class;

        $this->entityManager
            ->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($customMetadata));

        $this->assertInstanceOf(
            DDC753DefaultRepository::class,
            $this->repositoryFactory->getRepository($this->entityManager, self::class)
        );
    }

    public function testCachesDistinctRepositoriesPerDistinctEntityManager(): void
    {
        $em1 = $this->createEntityManager();
        $em2 = $this->createEntityManager();

        $em1->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnCallback([$this, 'buildClassMetadata']));

        $em2->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnCallback([$this, 'buildClassMetadata']));

        $repo1 = $this->repositoryFactory->getRepository($em1, self::class);
        $repo2 = $this->repositoryFactory->getRepository($em2, self::class);

        $this->assertSame($repo1, $this->repositoryFactory->getRepository($em1, self::class));
        $this->assertSame($repo2, $this->repositoryFactory->getRepository($em2, self::class));

        $this->assertNotSame($repo1, $repo2);
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|ClassMetadata
     *
     * @private
     */
    public function buildClassMetadata(string $className)
    {
        $metadata = $this->createMock(ClassMetadata::class);
        assert($metadata instanceof ClassMetadata || $metadata instanceof PHPUnit_Framework_MockObject_MockObject);

        $metadata->expects($this->any())->method('getName')->will($this->returnValue($className));

        $metadata->customRepositoryClassName = null;

        return $metadata;
    }

    /**
     * @return EntityManagerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private function createEntityManager()
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue($this->configuration));

        return $entityManager;
    }
}
