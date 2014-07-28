<?php

namespace Doctrine\Tests\ORM;

use Doctrine\Tests\OrmTestCase;
use Doctrine\ORM\EntityRepository;

class EntityRepositoryTest extends OrmTestCase
{
	public function testDependenciesAreAccessibleAfterInstantiation()
	{
		$entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
			->disableOriginalConstructor()
			->getMock();

		$classMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
			->disableOriginalConstructor()
			->getMock();

		$entityRepository = new EntityRepository($entityManager, $classMetadata);

		$this->assertSame($entityManager, $entityRepository->getEntityManager());
		$this->assertSame($classMetadata, $entityRepository->getClassMetadata());
	}
}
