<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\OrmTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

use function array_map;

#[Group('GH-10387')]
class GH10387Test extends OrmTestCase
{
    #[DataProvider('classHierachies')]
    public function testSchemaToolCreatesColumnForFieldInTheMiddleClass(array $classes): void
    {
        $em         = $this->getTestEntityManager();
        $schemaTool = new SchemaTool($em);
        $metadata   = array_map(static function (string $class) use ($em) {
            return $em->getClassMetadata($class);
        }, $classes);
        $schema     = $schemaTool->getSchemaFromMetadata([$metadata[0]]);

        self::assertNotNull($schema->getTable('root')->getColumn('middle_class_field'));
        self::assertNotNull($schema->getTable('root')->getColumn('leaf_class_field'));
    }

    public static function classHierachies(): Generator
    {
        yield 'hierarchy with Entity classes only' => [[GH10387EntitiesOnlyRoot::class, GH10387EntitiesOnlyMiddle::class, GH10387EntitiesOnlyLeaf::class]];
        yield 'MappedSuperclass in the middle of the hierarchy' => [[GH10387MappedSuperclassRoot::class, GH10387MappedSuperclassMiddle::class, GH10387MappedSuperclassLeaf::class]];
        yield 'abstract entity the the root and in the middle of the hierarchy' => [[GH10387AbstractEntitiesRoot::class, GH10387AbstractEntitiesMiddle::class, GH10387AbstractEntitiesLeaf::class]];
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'root')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorMap(['A' => GH10387EntitiesOnlyRoot::class, 'B' => GH10387EntitiesOnlyMiddle::class, 'C' => GH10387EntitiesOnlyLeaf::class])]
class GH10387EntitiesOnlyRoot
{
    /** @var string */
    #[ORM\Id]
    #[ORM\Column]
    private $id;
}

#[ORM\Entity]
class GH10387EntitiesOnlyMiddle extends GH10387EntitiesOnlyRoot
{
    /** @var string */
    #[ORM\Column(name: 'middle_class_field')]
    private $parentValue;
}

#[ORM\Entity]
class GH10387EntitiesOnlyLeaf extends GH10387EntitiesOnlyMiddle
{
    /** @var string */
    #[ORM\Column(name: 'leaf_class_field')]
    private $childValue;
}

/** ↓ This DiscriminatorMap contains the Entity classes only, not the Mapped Superclass */
#[ORM\DiscriminatorMap(['A' => GH10387MappedSuperclassRoot::class, 'B' => GH10387MappedSuperclassLeaf::class])]
#[ORM\Entity]
#[ORM\Table(name: 'root')]
#[ORM\InheritanceType('SINGLE_TABLE')]
class GH10387MappedSuperclassRoot
{
    /** @var string */
    #[ORM\Id]
    #[ORM\Column]
    private $id;
}

#[ORM\MappedSuperclass]
class GH10387MappedSuperclassMiddle extends GH10387MappedSuperclassRoot
{
    /** @var string */
    #[ORM\Column(name: 'middle_class_field')]
    private $parentValue;
}

#[ORM\Entity]
class GH10387MappedSuperclassLeaf extends GH10387MappedSuperclassMiddle
{
    /** @var string */
    #[ORM\Column(name: 'leaf_class_field')]
    private $childValue;
}


/** ↓ This DiscriminatorMap contains the single non-abstract Entity class only */
#[ORM\DiscriminatorMap(['A' => GH10387AbstractEntitiesLeaf::class])]
#[ORM\Entity]
#[ORM\Table(name: 'root')]
#[ORM\InheritanceType('SINGLE_TABLE')]
abstract class GH10387AbstractEntitiesRoot
{
    /** @var string */
    #[ORM\Id]
    #[ORM\Column]
    private $id;
}

#[ORM\Entity]
abstract class GH10387AbstractEntitiesMiddle extends GH10387AbstractEntitiesRoot
{
    /** @var string */
    #[ORM\Column(name: 'middle_class_field')]
    private $parentValue;
}

#[ORM\Entity]
class GH10387AbstractEntitiesLeaf extends GH10387AbstractEntitiesMiddle
{
    /** @var string */
    #[ORM\Column(name: 'leaf_class_field')]
    private $childValue;
}
