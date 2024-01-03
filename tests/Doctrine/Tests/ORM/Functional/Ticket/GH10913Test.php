<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Internal\TopologicalSort\CycleDetectedException;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

use function array_filter;
use function array_values;
use function strpos;

class GH10913Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH10913Entity::class,
        ]);
    }

    public function testExample1(): void
    {
        [$a, $b, $c] = $this->createEntities(3);

        $c->ref = $b;
        $b->odc = $a;

        $this->_em->persist($a);
        $this->_em->persist($b);
        $this->_em->persist($c);
        $this->_em->flush();

        $this->_em->remove($a);
        $this->_em->remove($b);
        $this->_em->remove($c);

        $this->flushAndAssertNumberOfDeleteQueries(3);
    }

    public function testExample2(): void
    {
        [$a, $b, $c] = $this->createEntities(3);

        $a->odc = $b;
        $b->odc = $a;
        $c->ref = $b;

        $this->_em->persist($a);
        $this->_em->persist($b);
        $this->_em->persist($c);
        $this->_em->flush();

        $this->_em->remove($a);
        $this->_em->remove($b);
        $this->_em->remove($c);

        $this->flushAndAssertNumberOfDeleteQueries(3);
    }

    public function testExample3(): void
    {
        [$a, $b, $c] = $this->createEntities(3);

        $a->odc = $b;
        $a->ref = $c;
        $c->ref = $b;
        $b->odc = $a;

        $this->_em->persist($a);
        $this->_em->persist($b);
        $this->_em->persist($c);
        $this->_em->flush();

        $this->_em->remove($a);
        $this->_em->remove($b);
        $this->_em->remove($c);

        self::expectException(CycleDetectedException::class);

        $this->_em->flush();
    }

    public function testExample4(): void
    {
        [$a, $b, $c, $d] = $this->createEntities(4);

        $a->ref = $b;
        $b->odc = $c;
        $c->odc = $b;
        $d->ref = $c;

        $this->_em->persist($a);
        $this->_em->persist($b);
        $this->_em->persist($c);
        $this->_em->persist($d);
        $this->_em->flush();

        $this->_em->remove($b);
        $this->_em->remove($c);
        $this->_em->remove($d);
        $this->_em->remove($a);

        $this->flushAndAssertNumberOfDeleteQueries(4);
    }

    private function flushAndAssertNumberOfDeleteQueries(int $expectedCount): void
    {
        $queryLog = $this->getQueryLog();
        $queryLog->reset()->enable();

        $this->_em->flush();

        $queries = array_values(array_filter($queryLog->queries, static function (array $entry): bool {
            return strpos($entry['sql'], 'DELETE') === 0;
        }));

        self::assertCount($expectedCount, $queries);
    }

    /**
     * @return list<GH10913Entity>
     */
    private function createEntities(int $count = 1): array
    {
        $result = [];

        for ($i = 0; $i < $count; $i++) {
            $result[] = new GH10913Entity();
        }

        return $result;
    }
}

/** @ORM\Entity */
class GH10913Entity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity=GH10913Entity::class)
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     *
     * @var GH10913Entity
     */
    public $odc;

    /**
     * @ORM\ManyToOne(targetEntity=GH10913Entity::class)
     * @ORM\JoinColumn(nullable=true)
     *
     * @var GH10913Entity
     */
    public $ref;
}
