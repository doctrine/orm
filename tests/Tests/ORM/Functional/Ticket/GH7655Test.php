<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/** @see https://github.com/doctrine/orm/issues/7655 */
final class GH7655Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([GH7655Item::class]);
        $this->_em->getConnection()->executeStatement('DELETE FROM gh7655_item');
        $this->_em->persist(new GH7655Item(1));
        $this->_em->persist(new GH7655Item(4));
        $this->_em->flush();
        $this->_em->clear();
    }

    #[DataProvider('provideBetweenPredicates')]
    public function testParenthesizedLeftOperand(string $predicate, int $expectedId): void
    {
        $item = $this->_em->createQuery('SELECT i FROM ' . GH7655Item::class . ' i WHERE ' . $predicate)
            ->getSingleResult();

        self::assertInstanceOf(GH7655Item::class, $item);
        self::assertSame($expectedId, $item->id);
    }

    /** @return iterable<string, array{string, int}> */
    public static function provideBetweenPredicates(): iterable
    {
        $subquery = '(SELECT j.id FROM ' . GH7655Item::class . ' j WHERE j.id = i.id)';

        yield 'arithmetic BETWEEN' => ['(i.id - 1) BETWEEN 2 AND 4', 4];
        yield 'arithmetic NOT BETWEEN' => ['(i.id - 1) NOT BETWEEN 2 AND 4', 1];
        yield 'subquery BETWEEN' => [$subquery . ' BETWEEN 2 AND 4', 4];
        yield 'subquery NOT BETWEEN' => [$subquery . ' NOT BETWEEN 2 AND 4', 1];
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'gh7655_item')]
class GH7655Item
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        public int $id,
    ) {
    }
}
