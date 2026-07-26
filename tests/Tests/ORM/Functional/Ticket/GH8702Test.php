<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

use function defined;

#[Group('GH8702')]
class GH8702Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(GH8702Item::class);

        $this->_em->persist(new GH8702Item(1, 1));
        $this->_em->persist(new GH8702Item(2, 2));
        $this->_em->persist(new GH8702Item(3, 3));
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testAddingMultipleCriteriaOnTheSameField(): void
    {
        $from = defined(Criteria::class . '::ASC') ? Criteria::create(true) : Criteria::create();
        $from->where($from->expr()->gte('value', 2));

        $to = defined(Criteria::class . '::ASC') ? Criteria::create(true) : Criteria::create();
        $to->where($to->expr()->lte('value', 2));

        $items = $this->_em->createQueryBuilder()
            ->select('item')
            ->from(GH8702Item::class, 'item')
            ->addCriteria($from)
            ->addCriteria($to)
            ->getQuery()
            ->getResult();

        self::assertCount(1, $items);
        self::assertSame(2, $items[0]->id);
    }
}

#[Entity]
class GH8702Item
{
    public function __construct(
        #[Id]
        #[Column(type: 'integer')]
        public int $id,
        #[Column(type: 'integer')]
        public int $value,
    ) {
    }
}
