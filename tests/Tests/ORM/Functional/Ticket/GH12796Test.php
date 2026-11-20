<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

use function defined;
use function json_decode;

use const JSON_THROW_ON_ERROR;

#[Group('GH-12796')]
class GH12796Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! defined(Types::class . '::JSON_OBJECT')) {
            self::markTestSkipped('This test requires doctrine/dbal >= 4.3');
        }

        $this->createSchemaForModels(GH12796Entity::class);
    }

    public function testMutatingNestedObjectThenCloningProducesChangeSet(): void
    {
        $entity       = new GH12796Entity();
        $entity->json = json_decode('{"foo":{"bar":""}}', false, 512, JSON_THROW_ON_ERROR);

        $this->_em->persist($entity);
        $this->_em->flush();
        $id = $entity->id;
        $this->_em->clear();

        $entity = $this->_em->find(GH12796Entity::class, $id);
        self::assertInstanceOf(GH12796Entity::class, $entity);

        // The nested object is modified in place, then the root object is
        // cloned. Mutable object values are compared by identity, as in 3.7.
        $json           = $entity->json;
        $json->foo->bar = 'baz';
        $entity->json   = clone $json;

        $uow = $this->_em->getUnitOfWork();
        $uow->computeChangeSets();

        self::assertArrayHasKey('json', $uow->getEntityChangeSet($entity));
    }
}

#[ORM\Entity]
class GH12796Entity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int|null $id = null;

    #[ORM\Column(type: 'json_object')]
    public object $json;
}
