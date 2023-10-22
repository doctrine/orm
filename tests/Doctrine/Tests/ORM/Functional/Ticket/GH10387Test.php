<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\OrmTestCase;
use Generator;

use function array_map;

/**
 * @group GH-10387
 */
class GH10387Test extends OrmTestCase
{
    /**
     * @dataProvider classHierachies
     */
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

/**
 * @ORM\Entity
 * @ORM\Table(name="root")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorMap({ "A": "GH10387EntitiesOnlyRoot", "B": "GH10387EntitiesOnlyMiddle", "C": "GH10387EntitiesOnlyLeaf"})
 */
class GH10387EntitiesOnlyRoot
{
    /**
     * @ORM\Id
     * @ORM\Column
     *
     * @var string
     */
    private $id;
}

/**
 * @ORM\Entity
 */
class GH10387EntitiesOnlyMiddle extends GH10387EntitiesOnlyRoot
{
    /**
     * @ORM\Column(name="middle_class_field")
     *
     * @var string
     */
    private $parentValue;
}

/**
 * @ORM\Entity
 */
class GH10387EntitiesOnlyLeaf extends GH10387EntitiesOnlyMiddle
{
    /**
     * @ORM\Column(name="leaf_class_field")
     *
     * @var string
     */
    private $childValue;
}

/**
 * @ORM\Entity
 * @ORM\Table(name="root")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorMap({ "A": "GH10387MappedSuperclassRoot", "B": "GH10387MappedSuperclassLeaf"})
 * ^- This DiscriminatorMap contains the Entity classes only, not the Mapped Superclass
 */
class GH10387MappedSuperclassRoot
{
    /**
     * @ORM\Id
     * @ORM\Column
     *
     * @var string
     */
    private $id;
}

/**
 * @ORM\MappedSuperclass
 */
class GH10387MappedSuperclassMiddle extends GH10387MappedSuperclassRoot
{
    /**
     * @ORM\Column(name="middle_class_field")
     *
     * @var string
     */
    private $parentValue;
}

/**
 * @ORM\Entity
 */
class GH10387MappedSuperclassLeaf extends GH10387MappedSuperclassMiddle
{
    /**
     * @ORM\Column(name="leaf_class_field")
     *
     * @var string
     */
    private $childValue;
}


/**
 * @ORM\Entity
 * @ORM\Table(name="root")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorMap({ "A": "GH10387AbstractEntitiesLeaf"})
 * ^- This DiscriminatorMap contains the single non-abstract Entity class only
 */
abstract class GH10387AbstractEntitiesRoot
{
    /**
     * @ORM\Id
     * @ORM\Column
     *
     * @var string
     */
    private $id;
}

/**
 * @ORM\Entity
 */
abstract class GH10387AbstractEntitiesMiddle extends GH10387AbstractEntitiesRoot
{
    /**
     * @ORM\Column(name="middle_class_field")
     *
     * @var string
     */
    private $parentValue;
}

/**
 * @ORM\Entity
 */
class GH10387AbstractEntitiesLeaf extends GH10387AbstractEntitiesMiddle
{
    /**
     * @ORM\Column(name="leaf_class_field")
     *
     * @var string
     */
    private $childValue;
}
