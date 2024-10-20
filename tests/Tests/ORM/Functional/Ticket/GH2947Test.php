<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Query;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;
use Stringable;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

#[Group('GH-2947')]
class GH2947Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->resultCache = new ArrayAdapter();

        parent::setUp();

        $this->createSchemaForModels(GH2947Car::class);
    }

    public function testIssue(): void
    {
        $this->createData();
        $this->getQueryLog()->reset()->enable();

        $query = $this->createQuery();
        self::assertEquals('BMW', (string) $query->getSingleResult());
        $this->assertQueryCount(1);

        $this->updateData();
        self::assertEquals('BMW', (string) $query->getSingleResult());
        $this->assertQueryCount(2);

        $query->expireResultCache(true);
        self::assertEquals('Dacia', (string) $query->getSingleResult());
        $this->assertQueryCount(3);

        $query->expireResultCache(false);
        self::assertEquals('Dacia', (string) $query->getSingleResult());
        $this->assertQueryCount(3);
    }

    private function createQuery(): Query
    {
        return $this->_em->createQueryBuilder()
                         ->select('car')
                         ->from(GH2947Car::class, 'car')
                         ->getQuery()
                         ->enableResultCache(3600, 'foo-cache-id');
    }

    private function createData(): void
    {
        $this->_em->persist(new GH2947Car('BMW'));
        $this->_em->flush();
        $this->_em->clear();
    }

    private function updateData(): void
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

#[Table(name: 'GH2947_car')]
#[Entity]
class GH2947Car implements Stringable
{
    public function __construct(
        #[Id]
        #[Column(type: 'string', length: 25)]
        #[GeneratedValue(strategy: 'NONE')]
        public string $brand,
    ) {
    }

    public function __toString(): string
    {
        return $this->brand;
    }
}
