<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use Generator;

class GH11199Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH11199Root::class,
            GH11199Parent::class,
            GH11199Foo::class,
            GH11199Baz::class,
            GH11199AbstractLeaf::class,
        ]);
    }

    public function dqlStatements(): Generator
    {
        yield ['SELECT e FROM ' . GH11199Root::class . ' e', "/WHERE g0_.asset_type IN \('root', 'foo', 'baz'\)$/"];
        yield ['SELECT e FROM ' . GH11199Parent::class . ' e', "/WHERE g0_.asset_type IN \('foo'\)$/"];
        yield ['SELECT e FROM ' . GH11199Foo::class . ' e', "/WHERE g0_.asset_type IN \('foo'\)$/"];
        yield ['SELECT e FROM ' . GH11199Baz::class . ' e', "/WHERE g0_.asset_type IN \('baz'\)$/"];
        yield ['SELECT e FROM ' . GH11199AbstractLeaf::class . ' e', '/WHERE 1=0/'];
    }

    /**
     * @dataProvider dqlStatements
     */
    public function testGH11199(string $dql, string $expectedDiscriminatorValues): void
    {
        $query = $this->_em->createQuery($dql);
        $sql   = $query->getSQL();

        self::assertMatchesRegularExpression($expectedDiscriminatorValues, $sql);
    }
}

/**
 * @ORM\Entity()
 * @ORM\Table(name="gh11199")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="asset_type", type="string")
 * @ORM\DiscriminatorMap({
 *     "root" = "\Doctrine\Tests\ORM\Functional\Ticket\GH11199Root",
 *     "foo"  = "\Doctrine\Tests\ORM\Functional\Ticket\GH11199Foo",
 *     "baz"  = "\Doctrine\Tests\ORM\Functional\Ticket\GH11199Baz",
 * })
 */
class GH11199Root
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     *
     * @var int|null
     */
    private $id = null;
}

/**
 * @ORM\Entity()
 */
abstract class GH11199Parent extends GH11199Root
{
}

/**
 * @ORM\Entity()
 */
class GH11199Foo extends GH11199Parent
{
}

/**
 * @ORM\Entity()
 */
class GH11199Baz extends GH11199Root
{
}

/**
 * @ORM\Entity()
 */
abstract class GH11199AbstractLeaf extends GH11199Root
{
}
