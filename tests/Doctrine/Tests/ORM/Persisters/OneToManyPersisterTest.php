<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Persisters\Collection\OneToManyPersister;
use Doctrine\Tests\DbalTypes\CustomIdObject;
use Doctrine\Tests\DbalTypes\CustomIdObjectType;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Models\OneToManyPersister\ChildClass;
use Doctrine\Tests\Models\OneToManyPersister\ParentClass;
use Doctrine\Tests\OrmTestCase;

use function array_pop;
use function assert;

/** @covers \Doctrine\ORM\Persisters\Collection\OneToManyPersister */
final class OneToManyPersisterTest extends OrmTestCase
{
    protected function setUp(): void
    {
        if (DBALType::hasType(CustomIdObjectType::NAME)) {
            DBALType::overrideType(CustomIdObjectType::NAME, CustomIdObjectType::class);
        } else {
            DBALType::addType(CustomIdObjectType::NAME, CustomIdObjectType::class);
        }

        parent::setUp();
    }

    /**
     * @group OneToManyPersister
     */
    public function testDeleteOneToManyCollection(): void
    {
        $parent = new ParentClass(new CustomIdObject('foo'));
        $child1 = new ChildClass(1, $parent);
        $child2 = new ChildClass(2, $parent);

        $parent->children->add($child1);
        $parent->children->add($child2);

        $em = $this->getTestEntityManager();
        $em->persist($parent);
        $em->flush();

        self::assertInstanceOf(PersistentCollection::class, $parent->children);

        $persister = new OneToManyPersister($em);
        $persister->delete($parent->children);

        $conn = $em->getConnection();
        assert($conn instanceof ConnectionMock);

        $updates       = $conn->getExecuteStatements();
        $lastStatement = array_pop($updates);

        self::assertSame('DELETE FROM onetomanypersister_child WHERE parent_id = ?', $lastStatement['sql']);
        self::assertSame([$parent->id], $lastStatement['params']);
        self::assertSame([CustomIdObjectType::NAME], $lastStatement['types']);
    }
}
