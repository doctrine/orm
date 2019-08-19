<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Utility;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Utility\HierarchyDiscriminatorResolver;
use PHPUnit\Framework\TestCase;

class HierarchyDiscriminatorResolverTest extends TestCase
{
    public function testResolveDiscriminatorsForClass() : void
    {
        $classMetadata                     = new ClassMetadata('Entity', null);
        $classMetadata->name               = 'Some\Class\Name';
        $classMetadata->discriminatorValue = 'discriminator';

        $childClassMetadata                     = new ClassMetadata('ChildEntity', $classMetadata);
        $childClassMetadata->name               = 'Some\Class\Child\Name';
        $childClassMetadata->discriminatorValue = 'child-discriminator';

        $classMetadata->setSubclasses([$childClassMetadata->getClassName()]);

        $em = $this->prophesize(EntityManagerInterface::class);
        $em->getClassMetadata($classMetadata->getClassName())
            ->shouldBeCalled()
            ->willReturn($classMetadata);
        $em->getClassMetadata($childClassMetadata->getClassName())
            ->shouldBeCalled()
            ->willReturn($childClassMetadata);

        $discriminators = HierarchyDiscriminatorResolver::resolveDiscriminatorsForClass($classMetadata, $em->reveal());

        self::assertCount(2, $discriminators);
        self::assertArrayHasKey($classMetadata->discriminatorValue, $discriminators);
        self::assertArrayHasKey($childClassMetadata->discriminatorValue, $discriminators);
    }

    public function testResolveDiscriminatorsForClassWithNoSubclasses() : void
    {
        $classMetadata = new ClassMetadata('Entity', null);
        $classMetadata->setSubclasses([]);
        $classMetadata->name               = 'Some\Class\Name';
        $classMetadata->discriminatorValue = 'discriminator';

        $em = $this->prophesize(EntityManagerInterface::class);

        $em->getClassMetadata($classMetadata->getClassName())
            ->shouldBeCalled()
            ->willReturn($classMetadata);

        $discriminators = HierarchyDiscriminatorResolver::resolveDiscriminatorsForClass($classMetadata, $em->reveal());

        self::assertCount(1, $discriminators);
        self::assertArrayHasKey($classMetadata->discriminatorValue, $discriminators);
    }
}
