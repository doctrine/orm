<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Utility;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Utility\HierarchyDiscriminatorResolver;
use PHPUnit\Framework\TestCase;

class HierarchyDiscriminatorResolverTest extends TestCase
{
    public function testResolveDiscriminatorsForClass(): void
    {
        $childClassMetadata                     = new ClassMetadata('ChildEntity');
        $childClassMetadata->name               = 'Some\Class\Child\Name';
        $childClassMetadata->discriminatorValue = 'child-discriminator';

        $classMetadata                     = new ClassMetadata('Entity');
        $classMetadata->subClasses         = [$childClassMetadata->name];
        $classMetadata->name               = 'Some\Class\Name';
        $classMetadata->discriminatorValue = 'discriminator';

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap(
                [
                    [$classMetadata->name, $classMetadata],
                    [$childClassMetadata->name, $childClassMetadata],
                ],
            );

        $discriminators = HierarchyDiscriminatorResolver::resolveDiscriminatorsForClass($classMetadata, $em);

        self::assertCount(2, $discriminators);
        self::assertArrayHasKey($classMetadata->discriminatorValue, $discriminators);
        self::assertArrayHasKey($childClassMetadata->discriminatorValue, $discriminators);
    }

    public function testResolveDiscriminatorsForClassWithNoSubclasses(): void
    {
        $classMetadata                     = new ClassMetadata('Entity');
        $classMetadata->subClasses         = [];
        $classMetadata->name               = 'Some\Class\Name';
        $classMetadata->discriminatorValue = 'discriminator';

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::exactly(1))
            ->method('getClassMetadata')
            ->with($classMetadata->name)
            ->willReturn($classMetadata);

        $discriminators = HierarchyDiscriminatorResolver::resolveDiscriminatorsForClass($classMetadata, $em);

        self::assertCount(1, $discriminators);
        self::assertArrayHasKey($classMetadata->discriminatorValue, $discriminators);
    }
}
