<?php

namespace Doctrine\Tests\ORM\Repository;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use PHPUnit_Framework_TestCase;

/**
 * Tests for {@see \Doctrine\ORM\Repository\DefaultRepositoryFactory}
 *
 * @covers \Doctrine\ORM\Repository\DefaultRepositoryFactory
 */
class DefaultRepositoryFactoryTest extends PHPUnit_Framework_TestCase
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
        $this->entityManager     = $this->getMock('Doctrine\\ORM\\EntityManagerInterface');
        $this->configuration     = $this->getMock('Doctrine\\ORM\\Configuration');
        $this->repositoryFactory = new DefaultRepositoryFactory();

        $this
            ->entityManager
            ->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnCallback(array($this, 'buildClassMetadata')));

        $this
            ->entityManager
            ->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue($this->configuration));

        $this
            ->configuration
            ->expects($this->any())
            ->method('getDefaultRepositoryClassName')
            ->will($this->returnValue('Doctrine\\Tests\\Models\\DDC869\\DDC869PaymentRepository'));
    }

    public function testCreatesRepositoryFromDefaultRepositoryClass()
    {
        $this->assertInstanceOf(
            'Doctrine\\Tests\\Models\\DDC869\\DDC869PaymentRepository',
            $this->repositoryFactory->getRepository($this->entityManager, __CLASS__)
        );
    }

    /**
     * @private
     *
     * @param string $className
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Doctrine\Common\Persistence\Mapping\ClassMetadata
     */
    public function buildClassMetadata($className)
    {
        $metadata = $this
            ->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $metadata->expects($this->any())->method('getName')->will($this->returnValue($className));

        $metadata->customRepositoryClassName = null;

        return $metadata;
    }
}