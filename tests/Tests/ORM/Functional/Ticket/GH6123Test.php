<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH6123Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH6123Entity::class,
        );
    }

    public function testLoadingRemovedEntityFromDatabaseDoesNotCreateNewManagedEntityInstance(): void
    {
        $entity = new GH6123Entity();
        $this->_em->persist($entity);
        $this->_em->flush();

        self::assertSame(UnitOfWork::STATE_MANAGED, $this->_em->getUnitOfWork()->getEntityState($entity));
        self::assertFalse($this->_em->getUnitOfWork()->isScheduledForDelete($entity));

        $this->_em->remove($entity);

        $freshEntity = $this->loadEntityFromDatabase($entity->id);
        self::assertSame($entity, $freshEntity);

        self::assertSame(UnitOfWork::STATE_REMOVED, $this->_em->getUnitOfWork()->getEntityState($freshEntity));
        self::assertTrue($this->_em->getUnitOfWork()->isScheduledForDelete($freshEntity));
    }

    public function testRemovedEntityCanBePersistedAgain(): void
    {
        $entity = new GH6123Entity();
        $this->_em->persist($entity);
        $this->_em->flush();

        $this->_em->remove($entity);
        self::assertSame(UnitOfWork::STATE_REMOVED, $this->_em->getUnitOfWork()->getEntityState($entity));
        self::assertTrue($this->_em->getUnitOfWork()->isScheduledForDelete($entity));

        $this->loadEntityFromDatabase($entity->id);

        $this->_em->persist($entity);
        self::assertSame(UnitOfWork::STATE_MANAGED, $this->_em->getUnitOfWork()->getEntityState($entity));
        self::assertFalse($this->_em->getUnitOfWork()->isScheduledForDelete($entity));

        $this->_em->flush();
    }

    private function loadEntityFromDatabase(int $id): GH6123Entity|null
    {
        return $this->_em->createQueryBuilder()
            ->select('e')
            ->from(GH6123Entity::class, 'e')
            ->where('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

#[ORM\Entity]
class GH6123Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public int $id;
}
