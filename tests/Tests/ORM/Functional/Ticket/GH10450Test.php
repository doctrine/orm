<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Tests\OrmTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;

class GH10450Test extends OrmTestCase
{
    /** @param class-string $className */
    #[DataProvider('classesThatOverrideFieldNames')]
    public function testDuplicatePrivateFieldsShallBeRejected(string $className): void
    {
        $em = $this->getTestEntityManager();

        $this->expectException(MappingException::class);

        $em->getClassMetadata($className);
    }

    public static function classesThatOverrideFieldNames(): Generator
    {
        yield 'Entity class that redeclares a private field inherited from a base entity' => [GH10450EntityChildPrivate::class];
        yield 'Entity class that redeclares a private field inherited from a mapped superclass' => [GH10450MappedSuperclassChildPrivate::class];
        yield 'Entity class that redeclares a protected field inherited from a base entity' => [GH10450EntityChildProtected::class];
        yield 'Entity class that redeclares a protected field inherited from a mapped superclass' => [GH10450MappedSuperclassChildProtected::class];
    }

    public function testFieldsOfTransientClassesAreNotConsideredDuplicate(): void
    {
        $em = $this->getTestEntityManager();

        $metadata = $em->getClassMetadata(GH10450Cat::class);

        self::assertArrayHasKey('id', $metadata->fieldMappings);
    }
}

#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorMap(['base' => GH10450BaseEntityPrivate::class, 'child' => GH10450EntityChildPrivate::class])]
#[ORM\DiscriminatorColumn(name: 'type')]
class GH10450BaseEntityPrivate
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'text', name: 'base')]
    private string $field;
}

#[ORM\Entity]
class GH10450EntityChildPrivate extends GH10450BaseEntityPrivate
{
    #[ORM\Column(type: 'text', name: 'child')]
    private string $field;
}

#[ORM\MappedSuperclass]
class GH10450BaseMappedSuperclassPrivate
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'text', name: 'base')]
    private string $field;
}

#[ORM\Entity]
class GH10450MappedSuperclassChildPrivate extends GH10450BaseMappedSuperclassPrivate
{
    #[ORM\Column(type: 'text', name: 'child')]
    private string $field;
}

#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorMap(['base' => GH10450BaseEntityProtected::class, 'child' => GH10450EntityChildProtected::class])]
#[ORM\DiscriminatorColumn(name: 'type')]
class GH10450BaseEntityProtected
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $id;

    #[ORM\Column(type: 'text', name: 'base')]
    protected string $field;
}

#[ORM\Entity]
class GH10450EntityChildProtected extends GH10450BaseEntityProtected
{
    #[ORM\Column(type: 'text', name: 'child')]
    protected string $field;
}

#[ORM\MappedSuperclass]
class GH10450BaseMappedSuperclassProtected
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $id;

    #[ORM\Column(type: 'text', name: 'base')]
    protected string $field;
}

#[ORM\Entity]
class GH10450MappedSuperclassChildProtected extends GH10450BaseMappedSuperclassProtected
{
    #[ORM\Column(type: 'text', name: 'child')]
    protected string $field;
}

abstract class GH10450AbstractEntity
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $id;
}

#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorMap(['cat' => GH10450Cat::class])]
#[ORM\DiscriminatorColumn(name: 'type')]
abstract class GH10450Animal extends GH10450AbstractEntity
{
    #[ORM\Column(type: 'text', name: 'base')]
    private string $field;
}

#[ORM\Entity]
class GH10450Cat extends GH10450Animal
{
}
