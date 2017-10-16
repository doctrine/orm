<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group 2947
 */
class GH2947Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->resultCacheImpl = new ArrayCache();

        parent::setUp();

        $this->_schemaTool->createSchema([$this->_em->getClassMetadata(GH2947Car::class)]);
    }

    public function testIssue()
    {
        $this->createData();
        $initialQueryCount = $this->getCurrentQueryCount();

        $query = $this->createQuery();
        self::assertEquals('BMW', (string) $query->getSingleResult());
        self::assertEquals($initialQueryCount + 1, $this->getCurrentQueryCount());

        $this->updateData();
        self::assertEquals('BMW', (string) $query->getSingleResult());
        self::assertEquals($initialQueryCount + 2, $this->getCurrentQueryCount());

        $query->expireResultCache(true);
        self::assertEquals('Dacia', (string) $query->getSingleResult());
        self::assertEquals($initialQueryCount + 3, $this->getCurrentQueryCount());

        $query->expireResultCache(false);
        self::assertEquals('Dacia', (string) $query->getSingleResult());
        self::assertEquals($initialQueryCount + 3, $this->getCurrentQueryCount());
    }

    private function createQuery()
    {
        return $this->_em->createQueryBuilder()
                         ->select('car')
                         ->from(GH2947Car::class, 'car')
                         ->getQuery()
                         ->useResultCache(true, 3600, 'foo-cache-id');
    }

    private function createData()
    {
        $this->_em->persist(new GH2947Car('BMW'));
        $this->_em->flush();
        $this->_em->clear();
    }

    private function updateData()
    {
        $this->_em->createQueryBuilder()
                  ->update(GH2947Car::class, 'car')
                  ->set('car.brand', ':newBrand')
                  ->where('car.brand = :oldBrand')
                  ->setParameter('newBrand', 'Dacia')
                  ->setParameter('oldBrand', 'BMW')
                  ->getQuery()
                  ->execute();
    }
}

/**
 * @Entity
 * @Table(name="GH2947_car")
 */
class GH2947Car
{
    /**
     * @Id
     * @Column(type="string", length=25)
     * @GeneratedValue(strategy="NONE")
     */
    public $brand;

    public function __construct(string $brand)
    {
        $this->brand = $brand;
    }

    public function __toString(): string
    {
        return $this->brand;
    }
}
