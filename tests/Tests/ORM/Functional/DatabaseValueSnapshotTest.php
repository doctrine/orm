<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

use function defined;
use function json_decode;
use function json_encode;
use function str_repeat;

use const JSON_THROW_ON_ERROR;

#[Group('GH-12796')]
class DatabaseValueSnapshotTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! defined(Types::class . '::JSON_OBJECT')) {
            self::markTestSkipped('This test requires doctrine/dbal >= 4.3');
        }

        $this->createSchemaForModels(DatabaseValueSnapshotEntity::class);
    }

    public function testMutatingOriginalThenCloningProducesChangeSet(): void
    {
        $entity = $this->loadEntity('{"foo":{"bar":""}}');

        $json           = $entity->json;
        $json->foo->bar = 'baz';
        $entity->json   = clone $json;

        self::assertArrayHasKey('json', $this->computeChangeSet($entity));
    }

    public function testMutatingOriginalThenCloningProducesChangeSetForLongValue(): void
    {
        $entity = $this->loadEntity('{"foo":{"bar":"' . str_repeat('a', 300) . '"}}');

        $json           = $entity->json;
        $json->foo->bar = str_repeat('b', 300);
        $entity->json   = clone $json;

        self::assertArrayHasKey('json', $this->computeChangeSet($entity));
    }

    public function testEqualReplacementDoesNotProduceChangeSet(): void
    {
        $entity = $this->loadEntity('{"foo":{"bar":"baz"}}');

        $entity->json = json_decode(json_encode($entity->json), false, 512, JSON_THROW_ON_ERROR);

        self::assertArrayNotHasKey('json', $this->computeChangeSet($entity));
    }

    public function testInPlaceMutationWithoutReplacementDoesNotProduceChangeSet(): void
    {
        $entity = $this->loadEntity('{"foo":{"bar":""}}');

        $entity->json->foo->bar = 'baz';

        self::assertArrayNotHasKey('json', $this->computeChangeSet($entity));
    }

    public function testDifferentValueProducesChangeSet(): void
    {
        $entity = $this->loadEntity('{"foo":{"bar":""}}');

        $entity->json = json_decode('{"foo":{"bar":"baz"}}', false, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('json', $this->computeChangeSet($entity));
    }

    private function loadEntity(string $json): DatabaseValueSnapshotEntity
    {
        $entity       = new DatabaseValueSnapshotEntity();
        $entity->json = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

        $this->_em->persist($entity);
        $this->_em->flush();
        $id = $entity->id;
        $this->_em->clear();

        $entity = $this->_em->find(DatabaseValueSnapshotEntity::class, $id);
        self::assertInstanceOf(DatabaseValueSnapshotEntity::class, $entity);

        return $entity;
    }

    /** @return mixed[] */
    private function computeChangeSet(DatabaseValueSnapshotEntity $entity): array
    {
        $uow = $this->_em->getUnitOfWork();
        $uow->computeChangeSets();

        return $uow->getEntityChangeSet($entity);
    }
}

#[ORM\Entity]
class DatabaseValueSnapshotEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int|null $id = null;

    #[ORM\Column(type: 'json_object')]
    public object $json;
}
