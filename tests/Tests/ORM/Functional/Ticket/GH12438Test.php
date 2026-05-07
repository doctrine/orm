<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use DateTimeImmutable;
use Doctrine\ORM\Query\Expr;
use Doctrine\Tests\Models\GH12438\VersionableEntity;
use Doctrine\Tests\Models\GH12438\VersionEntity;
use Doctrine\Tests\OrmFunctionalTestCase;
use SortDirection;

use function count;

class GH12438Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(VersionableEntity::class, VersionEntity::class);

        $this->generateFixture();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->dropTableIfExists('version');
        $this->dropTableIfExists('versionable');
    }

    public function testFetchAll(): void
    {
        /** @var VersionableEntity[] $versionables */
        $versionables = $this->_em->createQueryBuilder()
            ->select([
                'versionable',
                'version',
            ])
            ->from(VersionableEntity::class, 'versionable')
            ->innerJoin('versionable.versions', 'version')
            ->orderBy('versionable.id', SortDirection::Descending)
            ->getQuery()
            ->getResult();

        $this->assertCount(2, $versionables);

        $this->assertVersionable($versionables[0], [23, 24, 21, 22]);
        $this->assertVersionable($versionables[1], [13, 11, 12]);
    }

    public function testFetchWithJoinCondition(): void
    {
        $versionFqcn = VersionEntity::class;

        $condition = 'version.id IN (SELECT v.id FROM ' . $versionFqcn . ' AS v WHERE v.versionDate > :date ORDER BY v.id DESC)';

        /** @var VersionableEntity[] $versionables */
        $versionables = $this->_em->createQueryBuilder()
            ->select([
                'versionable',
                'version',
            ])
            ->from(VersionableEntity::class, 'versionable')
            ->leftJoin('versionable.versions', 'version', Expr\Join::WITH, $condition)
            ->setParameter('date', new DateTimeImmutable('2025-03-01'))
            ->orderBy('versionable.id', SortDirection::Descending)
            ->getQuery()
            ->getResult();

        $this->assertVersionable($versionables[0], [23, 24, 21]);
        $this->assertVersionable($versionables[1], [13]);
    }

    public function testFetchWithJoinConditionWithoutAssociation(): void
    {
        $versionFqcn = VersionEntity::class;

        $condition = 'version.id IN (SELECT v.id FROM ' . $versionFqcn . ' AS v WHERE v.versionDate > :date ORDER BY v.id DESC)';

        /** @var VersionableEntity[] $versionables */
        $versionables = $this->_em->createQueryBuilder()
            ->distinct()
            ->select('version.id')
            ->from(VersionableEntity::class, 'versionable')
            ->leftJoin($versionFqcn, 'version', Expr\Join::ON, $condition)
            ->setParameter('date', new DateTimeImmutable('2025-03-01'))
            ->orderBy('version.id', SortDirection::Ascending)
            ->getQuery()
            ->getSingleColumnResult();

        $this->assertIsArray($versionables);
        $this->assertCount(4, $versionables);
        $this->assertEquals([13, 21, 23, 24], $versionables);
    }

    private function generateFixture(): void
    {
        $versionable1 = new VersionableEntity(1);

        $version11 = new VersionEntity(11);
        $version11->setVersionDate(new DateTimeImmutable('2024-02-01'));
        $version11->setVersionable($versionable1);

        $version12 = new VersionEntity(12);
        $version12->setVersionDate(new DateTimeImmutable('2024-01-01'));
        $version12->setVersionable($versionable1);

        $version13 = new VersionEntity(13);
        $version13->setVersionDate(new DateTimeImmutable('2025-03-02'));
        $version13->setVersionable($versionable1);

        $versionable2 = new VersionableEntity(2);

        $version21 = new VersionEntity(21);
        $version21->setVersionDate(new DateTimeImmutable('2025-04-01'));
        $version21->setVersionable($versionable2);

        $version22 = new VersionEntity(22);
        $version22->setVersionDate(new DateTimeImmutable('2025-03-01'));
        $version22->setVersionable($versionable2);

        $version23 = new VersionEntity(23);
        $version23->setVersionDate(new DateTimeImmutable('2025-06-01'));
        $version23->setVersionable($versionable2);

        $version24 = new VersionEntity(24);
        $version24->setVersionDate(new DateTimeImmutable('2025-05-01'));
        $version24->setVersionable($versionable2);

        $this->_em->persist($versionable1);
        $this->_em->persist($version11);
        $this->_em->persist($version12);
        $this->_em->persist($version13);

        $this->_em->persist($versionable2);
        $this->_em->persist($version21);
        $this->_em->persist($version22);
        $this->_em->persist($version23);
        $this->_em->persist($version24);

        $this->_em->flush();
        $this->_em->clear();
    }

    private function assertVersionable(VersionableEntity $versionable, array $expectedIds): void
    {
        $versions = $versionable->getVersions();

        $size = count($expectedIds);

        $this->assertCount($size, $versions);

        foreach ($expectedIds as $index => $expectedId) {
            $version = $versions->get($index);

            $this->assertInstanceOf(VersionEntity::class, $version);
            $this->assertEquals($expectedId, $version->getId());
        }
    }
}
