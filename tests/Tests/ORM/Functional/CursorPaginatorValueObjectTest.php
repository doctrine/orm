<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Tools\Pagination\CursorPaginator;
use Doctrine\Tests\DbalTypes\BinaryCode;
use Doctrine\Tests\DbalTypes\BinaryCodeType;
use Doctrine\Tests\Models\Pagination\CodedItem;
use Doctrine\Tests\OrmFunctionalTestCase;

use function array_map;
use function sprintf;

class CursorPaginatorValueObjectTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (Type::hasType(BinaryCodeType::NAME)) {
            Type::overrideType(BinaryCodeType::NAME, BinaryCodeType::class);
        } else {
            Type::addType(BinaryCodeType::NAME, BinaryCodeType::class);
        }

        $this->createSchemaForModels(CodedItem::class);
    }

    protected function tearDown(): void
    {
        $this->_em->getConnection()->executeStatement('DELETE FROM pagination_coded_item');

        parent::tearDown();
    }

    public function testEncodedCursorsNavigateOverValueObjectsStoredAsBinary(): void
    {
        // 0xff never appears in UTF-8, so none of the database values can be encoded as JSON
        for ($i = 5; $i >= 1; --$i) {
            $this->_em->persist(new CodedItem(new BinaryCode(sprintf('ff%06x', $i))));
        }

        $this->_em->flush();
        $this->_em->clear();

        $query     = $this->_em->createQuery('SELECT i FROM Doctrine\Tests\Models\Pagination\CodedItem i ORDER BY i.code ASC');
        $paginator = new CursorPaginator(2);
        $codes     = static fn (array $items): array => array_map(static fn (CodedItem $item): string => (string) $item->code, $items);

        $firstPage = $paginator->paginate($query);
        self::assertSame(['ff000001', 'ff000002'], $codes($firstPage->getItems()));

        $secondPage = $paginator->paginate($query, $firstPage->getNextCursorAsString());
        self::assertSame(['ff000003', 'ff000004'], $codes($secondPage->getItems()));

        $thirdPage = $paginator->paginate($query, $secondPage->getNextCursorAsString());
        self::assertSame(['ff000005'], $codes($thirdPage->getItems()));

        $previousPage = $paginator->paginate($query, $thirdPage->getPreviousCursorAsString());
        self::assertSame(['ff000003', 'ff000004'], $codes($previousPage->getItems()));
    }
}
