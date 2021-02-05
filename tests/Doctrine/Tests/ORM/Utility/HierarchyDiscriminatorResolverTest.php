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

        $em = $this->prophesize(EntityManagerInterface::class);
        $em->getClassMetadata($classMetadata->name)
            ->shouldBeCalled()
            ->willReturn($classMetadata);
        $em->getClassMetadata($childClassMetadata->name)
            ->shouldBeCalled()
            ->willReturn($childClassMetadata);

        $discriminators = HierarchyDiscriminatorResolver::resolveDiscriminatorsForClass($classMetadata, $em->reveal());

        $this->assertCount(2, $discriminators);
        $this->assertArrayHasKey($classMetadata->discriminatorValue, $discriminators);
        $this->assertArrayHasKey($childClassMetadata->discriminatorValue, $discriminators);
    }

    public function testResolveDiscriminatorsForClassWithNoSubclasses(): void
    {
        $classMetadata                     = new ClassMetadata('Entity');
        $classMetadata->subClasses         = [];
        $classMetadata->name               = 'Some\Class\Name';
        $classMetadata->discriminatorValue = 'discriminator';

        $em = $this->prophesize(EntityManagerInterface::class);
        $em->getClassMetadata($classMetadata->name)
            ->shouldBeCalled()
            ->willReturn($classMetadata);

        $discriminators = HierarchyDiscriminatorResolver::resolveDiscriminatorsForClass($classMetadata, $em->reveal());

        $this->assertCount(1, $discriminators);
        $this->assertArrayHasKey($classMetadata->discriminatorValue, $discriminators);
    }
}
