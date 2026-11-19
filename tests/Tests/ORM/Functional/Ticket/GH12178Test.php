<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('GH12178')]
class GH12178Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(GH12178Period::class);

        $this->_em->persist(new GH12178Period(1, 2));
        $this->_em->persist(new GH12178Period(5, null));
        $this->_em->persist(new GH12178Period(3, 9));
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testCaseExpressionAsLeftOperandOfInExpression(): void
    {
        $ids = $this->_em->createQuery(
            'SELECT p.id FROM ' . GH12178Period::class . ' p'
            . ' WHERE CASE WHEN p.end IS NULL THEN p.start ELSE p.end END IN (2, 5)'
            . ' ORDER BY p.id',
        )->getSingleColumnResult();

        self::assertSame([1, 2], $ids);
    }
}

#[ORM\Entity]
class GH12178Period
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public int $id;

    public function __construct(
        #[ORM\Column(name: 'period_start')]
        public int $start,
        #[ORM\Column(name: 'period_end', nullable: true)]
        public int|null $end,
    ) {
    }
}
