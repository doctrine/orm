<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Persisters\Collection\ManyToManyPersister;
use Doctrine\Tests\Mocks\ArrayResultFactory;
use Doctrine\Tests\Models\Enums\AccessLevel;
use Doctrine\Tests\Models\ManyToManyPersister\ChildClass;
use Doctrine\Tests\Models\ManyToManyPersister\OtherParentClass;
use Doctrine\Tests\Models\ManyToManyPersister\ParentClass;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

use function array_fill;
use function count;
use function defined;

#[CoversClass(ManyToManyPersister::class)]
final class ManyToManyPersisterTest extends OrmTestCase
{
    #[Group('GH-6991')]
    #[Group('ManyToManyPersister')]
    public function testDeleteManyToManyCollection(): void
    {
        $driver = $this->createSTub(Driver::class);
        $driver->method('connect')
            ->willReturn($this->createSTub(Driver\Connection::class));

        $connection = $this->getMockBuilder(Connection::class)
            ->setConstructorArgs([[], $driver])
            ->onlyMethods(['executeStatement', 'getDatabasePlatform'])
            ->getMock();
        $connection->method('getDatabasePlatform')
            ->willReturn(new SQLitePlatform());

        $parent      = new ParentClass(1);
        $otherParent = new OtherParentClass(42);
        $child       = new ChildClass(1, $otherParent);

        $parent->children->add($child);
        $child->parents->add($parent);

        $em = $this->createTestEntityManagerWithConnection($connection);
        $em->persist($parent);
        $em->flush();

        $childReloaded = $em->find(ChildClass::class, ['id1' => 1, 'otherParent' => $otherParent]);
        self::assertInstanceOf(ChildClass::class, $childReloaded);

        $connection->expects($this->once())
            ->method('executeStatement')
            ->with('DELETE FROM parent_child WHERE child_id1 = ? AND child_id2 = ?', [1, 42]);

        $persister = new ManyToManyPersister($em);
        $persister->delete($childReloaded->parents);
    }

    #[DataProvider('provideCollectionSides')]
    public function testLoadCriteriaPreservesParameterOrderAndTypes(bool $owningSide): void
    {
        $driver     = $this->createStub(Driver::class);
        $connection = $this->getMockBuilder(Connection::class)
            ->setConstructorArgs([[], $driver])
            ->onlyMethods(['executeQuery', 'getDatabasePlatform'])
            ->getMock();
        $connection->method('getDatabasePlatform')->willReturn(new SQLitePlatform());

        $em = $this->createTestEntityManagerWithConnection($connection);

        if ($owningSide) {
            $owner         = new ChildClass(7, new OtherParentClass(42));
            $identifier    = ['id1' => 7, 'otherParent' => 42];
            $association   = 'parents';
            $field         = 'id';
            $expectedWhere = ' WHERE t.child_id1 = ? AND t.child_id2 = ?';
            $expected      = [7, 42];
        } else {
            $owner         = new ParentClass(7);
            $identifier    = ['id' => 7];
            $association   = 'children';
            $field         = 'id1';
            $expectedWhere = ' WHERE t.parent_id = ?';
            $expected      = [7];
        }

        $em->getUnitOfWork()->registerManaged($owner, $identifier, []);
        $mapping    = $em->getClassMetadata($owner::class)->associationMappings[$association];
        $collection = new PersistentCollection($em, $em->getClassMetadata($mapping->targetEntity), new ArrayCollection());
        $collection->setOwner($owner, $mapping);

        $criteria = defined(Criteria::class . '::ASC') ? Criteria::create(true) : Criteria::create();
        $criteria->where(Criteria::expr()->in($field, [9 => AccessLevel::Admin, 'null' => null, 2 => AccessLevel::Admin, 'last' => 2]));
        $criteria->andWhere(Criteria::expr()->notIn($field, [3, 4]));
        $criteria->andWhere(Criteria::expr()->eq($field, 1));

        $expectedWhere .= ' AND te.' . $field . ' IN (?, ?, ?, ?) AND te.' . $field . ' NOT IN (?, ?) AND te.' . $field . ' = ?';
        $expected       = [...$expected, 1, null, 1, 2, 3, 4, 1];

        $connection->expects($this->once())
            ->method('executeQuery')
            ->with(self::stringEndsWith($expectedWhere), $expected, array_fill(0, count($expected), 'integer'))
            ->willReturn(ArrayResultFactory::createWrapperResultFromArray([], $connection));

        self::assertSame([], (new ManyToManyPersister($em))->loadCriteria($collection, $criteria));
    }

    /** @return iterable<string, array{bool}> */
    public static function provideCollectionSides(): iterable
    {
        yield 'owning side with composite foreign identifier' => [true];
        yield 'inverse side' => [false];
    }
}
