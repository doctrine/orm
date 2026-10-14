<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

use function array_keys;

/** @see https://github.com/doctrine/orm/issues/12672 */
#[RequiresPhp('>=8.4.0')]
class GH12672Test extends OrmFunctionalTestCase
{
    private int $id;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->_em->getConfiguration()->isNativeLazyObjectsEnabled()) {
            $this->markTestSkipped('Test requires native lazy objects enabled.');
        }

        $this->createSchemaForModels(GH12672Entity::class, GH12672Related::class);

        $related = new GH12672Related();
        $this->_em->persist($related);

        $entity            = new GH12672Entity();
        $entity->related   = $related;
        $entity->name      = 'unchanged';
        $entity->date      = new DateTime('2020-07-10');
        $entity->immutable = new DateTimeImmutable('2020-07-10 12:00:00');

        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();

        $this->id = $entity->id;
    }

    public function testLazyInitOfPartialObjectDoesNotChangeMutableDateTimeField(): void
    {
        $this->assertNoChangesAfterLazyInit('date');
    }

    public function testLazyInitOfPartialObjectDoesNotChangeImmutableDateTimeField(): void
    {
        $this->assertNoChangesAfterLazyInit('immutable');
    }

    public function testLazyInitThroughEagerJoinDoesNotChangeDateTimeField(): void
    {
        $this->assertNoChangesAfterLazyInit('date', GH12672EagerEntity::class);
    }

    public function testDateTimeChangedBeforeLazyInitIsStillDetected(): void
    {
        $entity       = $this->loadPartially('date', GH12672Entity::class);
        $entity->date = new DateTime('2021-01-01');

        self::assertSame('unchanged', $entity->name);

        $uow = $this->_em->getUnitOfWork();
        $uow->computeChangeSets();
        self::assertSame(['date'], array_keys($uow->getEntityChangeSet($entity)));
    }

    /** @param class-string<GH12672Entity|GH12672EagerEntity> $className */
    private function assertNoChangesAfterLazyInit(string $loadedField, string $className = GH12672Entity::class): void
    {
        $entity = $this->loadPartially($loadedField, $className);

        // Reading a field the partial query did not load triggers lazy initialization.
        self::assertSame('unchanged', $entity->name);

        $uow = $this->_em->getUnitOfWork();
        $uow->computeChangeSets();
        self::assertSame([], $uow->getEntityChangeSet($entity));

        $this->getQueryLog()->reset()->enable();
        $this->_em->flush();
        $this->assertQueryCount(0, 'Flushing an unmodified entity must not issue an UPDATE.');
    }

    /** @param class-string<GH12672Entity|GH12672EagerEntity> $className */
    private function loadPartially(string $loadedField, string $className): GH12672Entity|GH12672EagerEntity
    {
        return $this->_em->createQuery(
            'SELECT PARTIAL e.{id, ' . $loadedField . '} FROM ' . $className . ' e WHERE e.id = :id',
        )->setParameter('id', $this->id)->getSingleResult();
    }
}

#[ORM\Entity]
class GH12672Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int|null $id = null;

    #[ORM\Column]
    public string $name;

    #[ORM\Column(type: 'date')]
    public DateTime $date;

    #[ORM\Column(type: 'datetime_immutable')]
    public DateTimeImmutable $immutable;

    #[ORM\ManyToOne]
    public GH12672Related|null $related = null;
}

/** Same table, but lazy initialization loads the row through a fetch-join. */
#[ORM\Entity]
#[ORM\Table(name: 'GH12672Entity')]
class GH12672EagerEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int|null $id = null;

    #[ORM\Column]
    public string $name;

    #[ORM\Column(type: 'date')]
    public DateTime $date;

    #[ORM\Column(type: 'datetime_immutable')]
    public DateTimeImmutable $immutable;

    #[ORM\ManyToOne(fetch: 'EAGER')]
    public GH12672Related|null $related = null;
}

#[ORM\Entity]
class GH12672Related
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int|null $id = null;
}
