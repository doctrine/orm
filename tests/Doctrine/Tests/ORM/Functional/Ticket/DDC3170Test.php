<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Internal\Hydration\HydrationException;

/**
 * @group DDC-2306
 */
class DDC3170Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(DDC3170AbstractEntityJoined::class),
                $this->em->getClassMetadata(DDC3170ProductJoined::class),
                $this->em->getClassMetadata(DDC3170AbstractEntitySingleTable::class),
                $this->em->getClassMetadata(DDC3170ProductSingleTable::class),
            ]
        );
    }

    /**
     * Tests that the discriminator column is correctly read from the meta mappings when fetching a
     * child from an inheritance mapped class.
     *
     * The simple object hydration maps the type field to a field alias like type2. This mapping needs
     * to be considered when loading the discriminator column's value from the SQL result.
     *
     * {@see \Doctrine\ORM\Internal\Hydration\SimpleObjectHydrator::hydrateRowData()}
     */
    public function testIssue()
    {
        $productJoined = new DDC3170ProductJoined();
        $productSingleTable = new DDC3170ProductSingleTable();

        $this->em->persist($productJoined);
        $this->em->persist($productSingleTable);
        $this->em->flush();
        $this->em->clear();

        $result = $this->em->createQueryBuilder()
                  ->select('p')
                  ->from(DDC3170ProductJoined::class, 'p')
                  ->getQuery()
                  ->getResult(AbstractQuery::HYDRATE_SIMPLEOBJECT);

        self::assertCount(1, $result);
        self::assertContainsOnly(DDC3170ProductJoined::class, $result);

        $result = $this->em->createQueryBuilder()
                  ->select('p')
                  ->from(DDC3170ProductSingleTable::class, 'p')
                  ->getQuery()
                  ->getResult(AbstractQuery::HYDRATE_SIMPLEOBJECT);

        self::assertCount(1, $result);
        self::assertContainsOnly(DDC3170ProductSingleTable::class, $result);
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"product" = "DDC3170ProductJoined"})
 */
abstract class DDC3170AbstractEntityJoined
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
}

/**
 * @ORM\Entity
 */
class DDC3170ProductJoined extends DDC3170AbstractEntityJoined
{
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"product" = "DDC3170ProductSingleTable"})
 */
abstract class DDC3170AbstractEntitySingleTable
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
}

/**
 * @ORM\Entity
 */
class DDC3170ProductSingleTable extends DDC3170AbstractEntitySingleTable
{
}
