<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

use function spl_object_id;

/** @see https://github.com/doctrine/orm/issues/12784 */
#[RequiresPhp('>=8.4.0')]
class GH12784Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->_em->getConfiguration()->isNativeLazyObjectsEnabled()) {
            $this->markTestSkipped('Test requires native lazy objects enabled.');
        }

        $this->createSchemaForModels(GH12784Entity::class);
    }

    public function testDetachedProxyDoesNotLeakPartialLoadStateToReusedObjectId(): void
    {
        $id = $this->createRow();

        $proxy    = $this->_em->getReference(GH12784Entity::class, $id);
        $objectId = spl_object_id($proxy);

        $this->_em->detach($proxy);
        unset($proxy);

        $this->assertNoUpdateForNewEntityWithObjectId($objectId);
    }

    public function testRemovedProxyDoesNotLeakPartialLoadStateToReusedObjectId(): void
    {
        $id = $this->createRow();

        $proxy    = $this->_em->getReference(GH12784Entity::class, $id);
        $objectId = spl_object_id($proxy);

        $this->_em->remove($proxy);
        $this->_em->flush();
        unset($proxy);

        $this->assertNoUpdateForNewEntityWithObjectId($objectId);
    }

    private function createRow(): int
    {
        $entity = new GH12784Entity();
        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();

        return $entity->id;
    }

    private function assertNoUpdateForNewEntityWithObjectId(int $objectId): void
    {
        $held = [];

        for ($i = 0; $i < 1000; $i++) {
            $entity = new GH12784Entity();

            if (spl_object_id($entity) === $objectId) {
                break;
            }

            $held[] = $entity;
        }

        self::assertSame($objectId, spl_object_id($entity), 'Could not reuse the object id of the released proxy.');

        $this->_em->persist($entity);
        $this->_em->flush();

        $this->_em->getUnitOfWork()->computeChangeSets();

        self::assertSame([], $this->_em->getUnitOfWork()->getEntityChangeSet($entity));
        self::assertFalse($this->_em->getUnitOfWork()->isScheduledForUpdate($entity));
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'gh12784_entity')]
class GH12784Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\Column]
    public string $name = 'new';
}
