<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Name\UnquotedIdentifierFolding;
use Doctrine\ORM\Persisters\Collection\ManyToManyPersister;
use Doctrine\Tests\Models\ManyToManyPersister\ChildClass;
use Doctrine\Tests\Models\ManyToManyPersister\OtherParentClass;
use Doctrine\Tests\Models\ManyToManyPersister\ParentClass;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

use function enum_exists;

#[CoversClass(ManyToManyPersister::class)]
final class ManyToManyPersisterTest extends OrmTestCase
{
    #[Group('GH-6991')]
    #[Group('ManyToManyPersister')]
    public function testDeleteManyToManyCollection(): void
    {
        $driver = $this->createMock(Driver::class);
        $driver->method('connect')
            ->willReturn($this->createMock(Driver\Connection::class));

        $connection = $this->getMockBuilder(Connection::class)
            ->setConstructorArgs([[], $driver])
            ->onlyMethods(['executeStatement', 'getDatabasePlatform'])
            ->getMock();
        $connection->method('getDatabasePlatform')
            ->willReturn($this->getMockForAbstractClass(AbstractPlatform::class, enum_exists(UnquotedIdentifierFolding::class) ? [UnquotedIdentifierFolding::UPPER] : []));

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
}
