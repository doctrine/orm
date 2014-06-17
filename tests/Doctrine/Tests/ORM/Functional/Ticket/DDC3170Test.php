<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

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
    protected function setup()
    {
        parent::setup();

        $this->_schemaTool->createSchema(
            array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC3170AbstractEntityJoined'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC3170ProductJoined'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC3170AbstractEntitySingleTable'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC3170ProductSingleTable'),
            )
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
        // $this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);

        $productJoined = new DDC3170ProductJoined();
        $productSingleTable = new DDC3170ProductSingleTable();
        $this->_em->persist($productJoined);
        $this->_em->persist($productSingleTable);
        $this->_em->flush();
        $this->_em->clear();

        try {
            $this->_em->createQueryBuilder()
                ->select('p')
                ->from(__NAMESPACE__ . '\\DDC3170ProductJoined', 'p')
                ->getQuery()
                ->getResult(AbstractQuery::HYDRATE_SIMPLEOBJECT);
        } catch (HydrationException $e) // Thrown by SimpleObjectHydrator
        {
            $this->fail('Failed correct mapping of discriminator column when using simple object hydration and class table inheritance');
        }

        try {
            $this->_em->createQueryBuilder()
                ->select('p')
                ->from(__NAMESPACE__ . '\\DDC3170ProductSingleTable', 'p')
                ->getQuery()
                ->getResult(AbstractQuery::HYDRATE_SIMPLEOBJECT);
        } catch (HydrationException $e) // Thrown by SimpleObjectHydrator
        {
            $this->fail('Failed correct mapping of discriminator column when using simple object hydration and single table inheritance');
        }
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({"product" = "DDC3170ProductJoined"})
 */
abstract class DDC3170AbstractEntityJoined
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
}

/**
 * @Entity
 */
class DDC3170ProductJoined extends DDC3170AbstractEntityJoined
{
}

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({"product" = "DDC3170ProductSingleTable"})
 */
abstract class DDC3170AbstractEntitySingleTable
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
}

/**
 * @Entity
 */
class DDC3170ProductSingleTable extends DDC3170AbstractEntitySingleTable
{
}
