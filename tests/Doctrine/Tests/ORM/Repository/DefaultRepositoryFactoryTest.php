<?php

namespace Doctrine\Tests\ORM\Repository;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Doctrine\Tests\Models\DDC753\DDC753DefaultRepository;
use Doctrine\Tests\Models\DDC869\DDC869PaymentRepository;
use PHPUnit\Framework\TestCase;

/**
 * Tests for {@see \Doctrine\ORM\Repository\DefaultRepositoryFactory}
 *
 * @covers \Doctrine\ORM\Repository\DefaultRepositoryFactory
 */
class DefaultRepositoryFactoryTest extends TestCase
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityManager;

    /**
     * @var \Doctrine\ORM\Configuration|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configuration;

    /**
     * @var DefaultRepositoryFactory
     */
    private $repositoryFactory;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->configuration     = $this->createMock(Configuration::class);
        $this->entityManager     = $this->createEntityManager();
        $this->repositoryFactory = new DefaultRepositoryFactory();

        $this->configuration
            ->expects($this->any())
            ->method('getDefaultRepositoryClassName')
            ->will($this->returnValue(DDC869PaymentRepository::class));
    }

    public function testCreatesRepositoryFromDefaultRepositoryClass()
    {
        $this->entityManager
            ->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnCallback([$this, 'buildClassMetadata']));

        $this->assertInstanceOf(
            DDC869PaymentRepository::class,
            $this->repositoryFactory->getRepository($this->entityManager, __CLASS__)
        );
    }

    public function testCreatedRepositoriesAreCached()
    {
        $this->entityManager
            ->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnCallback([$this, 'buildClassMetadata']));

        $this->assertSame(
            $this->repositoryFactory->getRepository($this->entityManager, __CLASS__),
            $this->repositoryFactory->getRepository($this->entityManager, __CLASS__)
        );
    }

    public function testCreatesRepositoryFromCustomClassMetadata()
    {
        $customMetadata = $this->buildClassMetadata(__DIR__);
        $customMetadata->customRepositoryClassName = DDC753DefaultRepository::class;

        $this->entityManager
            ->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($customMetadata));

        $this->assertInstanceOf(
            DDC753DefaultRepository::class,
            $this->repositoryFactory->getRepository($this->entityManager, __CLASS__)
        );
    }

    public function testCachesDistinctRepositoriesPerDistinctEntityManager()
    {
        $em1 = $this->createEntityManager();
        $em2 = $this->createEntityManager();

        $em1->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnCallback([$this, 'buildClassMetadata']));

        $em2->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnCallback([$this, 'buildClassMetadata']));

        $repo1 = $this->repositoryFactory->getRepository($em1, __CLASS__);
        $repo2 = $this->repositoryFactory->getRepository($em2, __CLASS__);

        $this->assertSame($repo1, $this->repositoryFactory->getRepository($em1, __CLASS__));
        $this->assertSame($repo2, $this->repositoryFactory->getRepository($em2, __CLASS__));

        $this->assertNotSame($repo1, $repo2);
    }

    /**
     * @private
     *
     * @param string $className
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Doctrine\ORM\Mapping\ClassMetadata
     */
    public function buildClassMetadata($className)
    {
        /* @var $metadata \Doctrine\ORM\Mapping\ClassMetadata|\PHPUnit_Framework_MockObject_MockObject */
        $metadata = $this->createMock(ClassMetadata::class);

        $metadata->expects($this->any())->method('getName')->will($this->returnValue($className));

        $metadata->customRepositoryClassName = null;

        return $metadata;
    }

    /**
     * @return \Doctrine\ORM\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
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
