<?php

namespace Doctrine\Tests\ORM\Utility;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Reflection\StaticReflectionService;
use Doctrine\ORM\Utility\HierarchyDiscriminatorResolver;
use PHPUnit\Framework\TestCase;

class HierarchyDiscriminatorResolverTest extends TestCase
{
    /**
     * @var Mapping\ClassMetadataBuildingContext
     */
    private $staticMetadataBuildingContext;

    public function setUp()
    {
        $this->staticMetadataBuildingContext = new Mapping\ClassMetadataBuildingContext(
            $this->createMock(Mapping\ClassMetadataFactory::class),
            new StaticReflectionService()
        );
    }

    public function testResolveDiscriminatorsForClass()
    {
        $childClassMetadata = new ClassMetadata('ChildEntity', $this->staticMetadataBuildingContext);
        $childClassMetadata->name = 'Some\Class\Child\Name';
        $childClassMetadata->discriminatorValue = 'child-discriminator';
        
        $classMetadata = new ClassMetadata('Entity', $this->staticMetadataBuildingContext);
        $classMetadata->setSubclasses([$childClassMetadata->getClassName()]);
        $classMetadata->name = 'Some\Class\Name';
        $classMetadata->discriminatorValue = 'discriminator';

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

    public function testResolveDiscriminatorsForClassWithNoSubclasses()
    {
        $classMetadata = new ClassMetadata('Entity', $this->staticMetadataBuildingContext);
        $classMetadata->setSubclasses([]);
        $classMetadata->name = 'Some\Class\Name';
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
