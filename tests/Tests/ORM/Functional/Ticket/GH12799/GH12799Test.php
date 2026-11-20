<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH12799;

use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter\SQLFilter\CompanySQLFilter;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use function count;
use function sort;

final class GH12799Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            Owner::class,
            SingleItem::class,
            SingleItemA::class,
            SingleItemB::class,
            JoinedItem::class,
            JoinedItemA::class,
            JoinedItemB::class,
        ]);

        $this->_em->getConfiguration()->addFilter(CompanySQLFilter::class, CompanySQLFilter::class);
    }

    /** @return iterable<string, array{class-string, class-string, class-string}> */
    public static function hierarchies(): iterable
    {
        yield 'single table' => [SingleItem::class, SingleItemA::class, SingleItemB::class];
        yield 'joined' => [JoinedItem::class, JoinedItemA::class, JoinedItemB::class];
    }

    /**
     * @param class-string<SingleItem|JoinedItem>   $root
     * @param class-string<SingleItemA|JoinedItemA> $classA
     * @param class-string<SingleItemB|JoinedItemB> $classB
     */
    #[DataProvider('hierarchies')]
    public function testSubclassesSharingAJoinColumnNameKeepDistinctAliasesAcrossFilterChanges(
        string $root,
        string $classA,
        string $classB,
    ): void {
        $ownerA       = new Owner();
        $ownerB       = new Owner();
        $itemA        = new $classA();
        $itemA->owner = $ownerA;
        $itemB        = new $classB();
        $itemB->owner = $ownerB;
        $this->_em->persist($ownerA);
        $this->_em->persist($ownerB);
        $this->_em->persist($itemA);
        $this->_em->persist($itemB);
        $this->_em->flush();

        $findBoth = function () use ($root, $classA, $classB, $itemA, $itemB, $ownerA, $ownerB): void {
            $this->_em->clear();
            $foundA = $this->_em->find($root, $itemA->id);
            $foundB = $this->_em->find($root, $itemB->id);
            self::assertInstanceOf($classA, $foundA);
            self::assertInstanceOf($classB, $foundB);
            self::assertSame($ownerA->id, $foundA->owner?->id);
            self::assertSame($ownerB->id, $foundB->owner?->id);
        };

        $filters = $this->_em->getFilters();
        $filters->enable(CompanySQLFilter::class)->setParameter('company', 'A');
        $findBoth();

        // Both owner_id join columns get their own result alias on the first build ...
        $firstBuild = $this->ownerIdAliases($root);
        self::assertCount(2, $firstBuild);
        $mappingSize = $this->mappingSize($root);

        for ($i = 0; $i < 5; $i++) {
            $filters->disable(CompanySQLFilter::class);
            $findBoth();
            $filters->enable(CompanySQLFilter::class)->setParameter('company', 'A');
            $findBoth();
        }

        // ... and every rebuild after a filter change reuses exactly those aliases.
        self::assertSame($firstBuild, $this->ownerIdAliases($root));
        self::assertSame($mappingSize, $this->mappingSize($root));
    }

    /** @return list<string> */
    private function ownerIdAliases(string $className): array
    {
        $aliases = [];
        foreach ($this->persister($className)->getResultSetMapping()->metaMappings as $alias => $column) {
            if ($column === 'owner_id') {
                $aliases[] = $alias;
            }
        }

        sort($aliases);

        return $aliases;
    }

    private function mappingSize(string $className): int
    {
        return count($this->persister($className)->getResultSetMapping()->columnOwnerMap);
    }

    private function persister(string $className): BasicEntityPersister
    {
        $persister = $this->_em->getUnitOfWork()->getEntityPersister($className);
        self::assertInstanceOf(BasicEntityPersister::class, $persister);

        return $persister;
    }
}
