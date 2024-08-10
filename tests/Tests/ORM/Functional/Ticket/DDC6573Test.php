<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\AbstractQuery;
use Doctrine\Tests\Models\DDC6573\DDC6573Currency;
use Doctrine\Tests\Models\DDC6573\DDC6573Item;
use Doctrine\Tests\Models\DDC6573\DDC6573Money;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-6573')]
final class DDC6573Test extends OrmFunctionalTestCase
{
    /** @var list<DDC6573Item> */
    private $fixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            DDC6573Item::class,
        );

        $item1 = new DDC6573Item('Plate', new DDC6573Money(5, new DDC6573Currency('GBP')));
        $item2 = new DDC6573Item('Iron', new DDC6573Money(50, new DDC6573Currency('EUR')));
        $item3 = new DDC6573Item('Teapot', new DDC6573Money(10, new DDC6573Currency('GBP')));

        $this->_em->persist($item1);
        $this->_em->persist($item2);
        $this->_em->persist($item3);

        $this->_em->flush();
        $this->_em->clear();

        $this->fixtures = [$item1, $item2, $item3];
    }

    protected function tearDown(): void
    {
        $this->_em->createQuery('DELETE FROM Doctrine\Tests\Models\DDC6573\DDC6573Item i')->execute();
    }

    public static function provideDataForHydrationMode(): iterable
    {
        yield [AbstractQuery::HYDRATE_ARRAY];
        yield [AbstractQuery::HYDRATE_OBJECT];
    }

    #[DataProvider('provideDataForHydrationMode')]
    public function testShouldSupportsMultipleNewOperator(int $hydrationMode): void
    {
        $dql = '
            SELECT
                new Doctrine\Tests\Models\DDC6573\DDC6573Money(
                    i.priceAmount,
                    new Doctrine\Tests\Models\DDC6573\DDC6573Currency(i.priceCurrency)
                )
            FROM
                Doctrine\Tests\Models\DDC6573\DDC6573Item i
            ORDER BY
                i.priceAmount ASC';

        $query  = $this->_em->createQuery($dql);
        $result = $query->getResult($hydrationMode);

        self::assertCount(3, $result);

        self::assertInstanceOf(DDC6573Money::class, $result[0]);
        self::assertInstanceOf(DDC6573Money::class, $result[1]);
        self::assertInstanceOf(DDC6573Money::class, $result[2]);

        self::assertEquals($this->fixtures[0]->getPrice(), $result[0]);
        self::assertEquals($this->fixtures[2]->getPrice(), $result[1]);
        self::assertEquals($this->fixtures[1]->getPrice(), $result[2]);
    }

    #[DataProvider('provideDataForHydrationMode')]
    public function testShouldSupportsBasicUsage(int $hydrationMode): void
    {
        $dql = '
            SELECT
                new Doctrine\Tests\Models\DDC6573\DDC6573Currency(
                    i.priceCurrency
                )
            FROM
                Doctrine\Tests\Models\DDC6573\DDC6573Item i
            ORDER BY
                i.priceAmount';

        $query  = $this->_em->createQuery($dql);
        $result = $query->getResult($hydrationMode);

        self::assertCount(3, $result);

        self::assertInstanceOf(DDC6573Currency::class, $result[0]);
        self::assertInstanceOf(DDC6573Currency::class, $result[1]);
        self::assertInstanceOf(DDC6573Currency::class, $result[2]);

        self::assertEquals($this->fixtures[0]->getPrice()->getCurrency(), $result[0]);
        self::assertEquals($this->fixtures[1]->getPrice()->getCurrency(), $result[2]);
        self::assertEquals($this->fixtures[2]->getPrice()->getCurrency(), $result[1]);
    }
}
