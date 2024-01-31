<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

use function substr;
use function strpos;
use function trim;
use function explode;
use function array_map;

class GH11199Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH11199Root::class,
            GH11199Parent::class,
            GH11199Foo::class,
            GH11199Bar::class,
            GH11199Baz::class,
        ]);
    }

    /**
     * @throws ORMException
     */
    public function testGH11199(): void
    {
        $em             = $this->getEntityManager();
        $sql            = $em->createQueryBuilder()->select('e')->from(GH11199Root::class, 'e')->getQuery()->getSQL();
        $condition      = substr($sql, strpos($sql, '('));
        $condition      = trim($condition, '()');
        $conditionParts = explode(',', $condition);
        $conditionParts = array_map('trim', $conditionParts);
        self::assertNotContains("''", $conditionParts, 'Discriminator column condition values contain empty string');
    }
}

/**
 * @ORM\Entity()
 * @ORM\Table(name="gh11199")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="asset_type", type="string")
 * @ORM\DiscriminatorMap({
 *     "foo" = "\Doctrine\Tests\ORM\Functional\Ticket\GH11199Foo",
 *     "bar" = "\Doctrine\Tests\ORM\Functional\Ticket\GH11199Bar",
 *     "baz" = "\Doctrine\Tests\ORM\Functional\Ticket\GH11199Baz",
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
class GH11199Bar extends GH11199Parent
{
}

/**
 * @ORM\Entity()
 */
class GH11199Baz extends GH11199Root
{
}
