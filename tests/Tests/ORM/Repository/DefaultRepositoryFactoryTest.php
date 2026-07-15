<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Repository;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Doctrine\Tests\Models\DDC753\DDC753DefaultRepository;
use Doctrine\Tests\Models\DDC753\DDC753EntityWithDefaultCustomRepository;
use Doctrine\Tests\Models\DDC869\DDC869PaymentRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

/**
 * Tests for {@see \Doctrine\ORM\Repository\DefaultRepositoryFactory}
 */
#[CoversClass(DefaultRepositoryFactory::class)]
class DefaultRepositoryFactoryTest extends TestCase
{
    private EntityManagerInterface&Stub $entityManager;
    private Configuration&Stub $configuration;
    private DefaultRepositoryFactory $repositoryFactory;

    protected function setUp(): void
    {
        $this->configuration     = $this->createStub(Configuration::class);
        $this->entityManager     = $this->createEntityManager();
        $this->repositoryFactory = new DefaultRepositoryFactory();

        $this->configuration
            ->method('getDefaultRepositoryClassName')
            ->willReturn(DDC869PaymentRepository::class);
    }

    public function testCreatesRepositoryFromDefaultRepositoryClass(): void
    {
        $this->entityManager
            ->method('getClassMetadata')
            ->willReturnCallback($this->buildClassMetadata(...));

        self::assertInstanceOf(
            DDC869PaymentRepository::class,
            $this->repositoryFactory->getRepository($this->entityManager, self::class),
        );
    }

    public function testCreatedRepositoriesAreCached(): void
    {
        $this->entityManager
            ->method('getClassMetadata')
            ->willReturnCallback($this->buildClassMetadata(...));

        self::assertSame(
            $this->repositoryFactory->getRepository($this->entityManager, self::class),
            $this->repositoryFactory->getRepository($this->entityManager, self::class),
        );
    }

    public function testCreatesRepositoryFromCustomClassMetadata(): void
    {
        $customMetadata                            = $this->buildClassMetadata(DDC753EntityWithDefaultCustomRepository::class);
        $customMetadata->customRepositoryClassName = DDC753DefaultRepository::class;

        $this->entityManager
            ->method('getClassMetadata')
            ->willReturn($customMetadata);

        self::assertInstanceOf(
            DDC753DefaultRepository::class,
            $this->repositoryFactory->getRepository($this->entityManager, self::class),
        );
    }

    public function testCachesDistinctRepositoriesPerDistinctEntityManager(): void
    {
        $em1 = $this->createEntityManager();
        $em2 = $this->createEntityManager();

        $em1->method('getClassMetadata')
            ->willReturnCallback($this->buildClassMetadata(...));

        $em2->method('getClassMetadata')
            ->willReturnCallback($this->buildClassMetadata(...));

        $repo1 = $this->repositoryFactory->getRepository($em1, self::class);
        $repo2 = $this->repositoryFactory->getRepository($em2, self::class);

        self::assertSame($repo1, $this->repositoryFactory->getRepository($em1, self::class));
        self::assertSame($repo2, $this->repositoryFactory->getRepository($em2, self::class));

        self::assertNotSame($repo1, $repo2);
    }

    /**
     * @param class-string<TEntity> $className
     *
     * @return ClassMetadata<TEntity>&Stub
     *
     * @template TEntity of object
     */
    private function buildClassMetadata(string $className): ClassMetadata&Stub
    {
        $metadata = $this->createStub(ClassMetadata::class);
        $metadata->method('getName')->willReturn($className);
        $metadata->name = $className;

        $metadata->customRepositoryClassName = null;

        return $metadata;
    }

    private function createEntityManager(): EntityManagerInterface&Stub
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getConfiguration')->willReturn($this->configuration);

        return $entityManager;
    }
}
