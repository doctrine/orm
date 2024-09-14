<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Repository;

use Closure;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Doctrine\Tests\Models\DDC753\DDC753DefaultRepository;
use Doctrine\Tests\Models\DDC753\DDC753EntityWithDefaultCustomRepository;
use Doctrine\Tests\Models\DDC869\DDC869PaymentRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for {@see \Doctrine\ORM\Repository\DefaultRepositoryFactory}
 *
 * @covers \Doctrine\ORM\Repository\DefaultRepositoryFactory
 */
class DefaultRepositoryFactoryTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private $entityManager;

    /** @var Configuration&MockObject */
    private $configuration;

    /** @var DefaultRepositoryFactory */
    private $repositoryFactory;

    protected function setUp(): void
    {
        $this->configuration     = $this->createMock(Configuration::class);
        $this->entityManager     = $this->createEntityManager();
        $this->repositoryFactory = new DefaultRepositoryFactory();

        $this->configuration
            ->expects(self::any())
            ->method('getDefaultRepositoryClassName')
            ->will(self::returnValue(DDC869PaymentRepository::class));
    }

    public function testCreatesRepositoryFromDefaultRepositoryClass(): void
    {
        $this->entityManager
            ->expects(self::any())
            ->method('getClassMetadata')
            ->will(self::returnCallback(Closure::fromCallable([$this, 'buildClassMetadata'])));

        self::assertInstanceOf(
            DDC869PaymentRepository::class,
            $this->repositoryFactory->getRepository($this->entityManager, self::class)
        );
    }

    public function testCreatedRepositoriesAreCached(): void
    {
        $this->entityManager
            ->expects(self::any())
            ->method('getClassMetadata')
            ->will(self::returnCallback(Closure::fromCallable([$this, 'buildClassMetadata'])));

        self::assertSame(
            $this->repositoryFactory->getRepository($this->entityManager, self::class),
            $this->repositoryFactory->getRepository($this->entityManager, self::class)
        );
    }

    public function testCreatesRepositoryFromCustomClassMetadata(): void
    {
        $customMetadata                            = $this->buildClassMetadata(DDC753EntityWithDefaultCustomRepository::class);
        $customMetadata->customRepositoryClassName = DDC753DefaultRepository::class;

        $this->entityManager
            ->expects(self::any())
            ->method('getClassMetadata')
            ->will(self::returnValue($customMetadata));

        self::assertInstanceOf(
            DDC753DefaultRepository::class,
            $this->repositoryFactory->getRepository($this->entityManager, self::class)
        );
    }

    public function testCachesDistinctRepositoriesPerDistinctEntityManager(): void
    {
        $em1 = $this->createEntityManager();
        $em2 = $this->createEntityManager();

        $em1->expects(self::any())
            ->method('getClassMetadata')
            ->will(self::returnCallback(Closure::fromCallable([$this, 'buildClassMetadata'])));

        $em2->expects(self::any())
            ->method('getClassMetadata')
            ->will(self::returnCallback(Closure::fromCallable([$this, 'buildClassMetadata'])));

        $repo1 = $this->repositoryFactory->getRepository($em1, self::class);
        $repo2 = $this->repositoryFactory->getRepository($em2, self::class);

        self::assertSame($repo1, $this->repositoryFactory->getRepository($em1, self::class));
        self::assertSame($repo2, $this->repositoryFactory->getRepository($em2, self::class));

        self::assertNotSame($repo1, $repo2);
    }

    /**
     * @psalm-param class-string<TEntity> $className
     *
     * @return ClassMetadata&MockObject
     * @psalm-return ClassMetadata<TEntity>&MockObject
     *
     * @template TEntity of object
     */
    private function buildClassMetadata(string $className): ClassMetadata
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getName')->willReturn($className);
        $metadata->name = $className;

        $metadata->customRepositoryClassName = null;

        return $metadata;
    }

    /** @return EntityManagerInterface&MockObject */
    private function createEntityManager(): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConfiguration')->willReturn($this->configuration);

        return $entityManager;
    }
}
