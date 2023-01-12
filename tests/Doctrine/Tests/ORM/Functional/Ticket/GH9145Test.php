<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\OrmTestCase;
use Generator;

use function array_map;

/**
 * @group GH-9145
 */
class GH9145Test extends OrmTestCase
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

    public function classHierachies(): Generator
    {
        yield 'hierarchy with Entity classes only' => [[GH9145_EntitiesOnly_Root::class, GH9145_EntitiesOnly_Middle::class, GH9145_EntitiesOnly_Leaf::class]];
        yield 'MappedSuperclass in the middle of the hierarchy' => [[GH9145_MappedSuperclass_Root::class, GH9145_MappedSuperclass_Middle::class, GH9145_MappedSuperclass_Leaf::class]];
        yield 'abstract entity the the root and in the middle of the hierarchy' => [[GH9145_AbstractEntities_Root::class, GH9145_AbstractEntities_Middle::class, GH9145_AbstractEntities_Leaf::class]];
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="root")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorMap({ "A": "GH9145_EntitiesOnly_Root", "B": "GH9145_EntitiesOnly_Middle", "C": "GH9145_EntitiesOnly_Leaf"})
 */
class GH9145_EntitiesOnly_Root
{
    /**
     * @ORM\Id
     * @ORM\Column
     */
    private $id;
}

/**
 * @ORM\Entity
 */
class GH9145_EntitiesOnly_Middle extends GH9145_EntitiesOnly_Root
{
    /** @ORM\Column(name="middle_class_field") */
    private $parentValue;
}

/**
 * @ORM\Entity
 */
class GH9145_EntitiesOnly_Leaf extends GH9145_EntitiesOnly_Middle
{
    /** @ORM\Column(name="leaf_class_field") */
    private $childValue;
}

/**
 * @ORM\Entity
 * @ORM\Table(name="root")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorMap({ "A": "GH9145_MappedSuperclass_Root", "B": "GH9145_MappedSuperclass_Leaf"})
 * ^- This DiscriminatorMap contains the Entity classes only, not the Mapped Superclass
 */
class GH9145_MappedSuperclass_Root
{
    /**
     * @ORM\Id
     * @ORM\Column
     */
    private $id;
}

/**
 * @ORM\MappedSuperclass
 */
class GH9145_MappedSuperclass_Middle extends GH9145_MappedSuperclass_Root
{
    /** @ORM\Column(name="middle_class_field") */
    private $parentValue;
}

/**
 * @ORM\Entity
 */
class GH9145_MappedSuperclass_Leaf extends GH9145_MappedSuperclass_Middle
{
    /** @ORM\Column(name="leaf_class_field") */
    private $childValue;
}


/**
 * @ORM\Entity
 * @ORM\Table(name="root")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorMap({ "A": "GH9145_AbstractEntities_Leaf"})
 * ^- This DiscriminatorMap contains the single non-abstract Entity class only
 */
abstract class GH9145_AbstractEntities_Root
{
    /**
     * @ORM\Id
     * @ORM\Column
     */
    private $id;
}

/**
 * @ORM\Entity
 */
abstract class GH9145_AbstractEntities_Middle extends GH9145_AbstractEntities_Root
{
    /** @ORM\Column(name="middle_class_field") */
    private $parentValue;
}

/**
 * @ORM\Entity
 */
class GH9145_AbstractEntities_Leaf extends GH9145_AbstractEntities_Middle
{
    /** @ORM\Column(name="leaf_class_field") */
    private $childValue;
}
