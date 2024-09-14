<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Cache;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\Tests\OrmFunctionalTestCase;

use function uniqid;

/** @group #6217 */
final class GH6217Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->enableSecondLevelCache();

        parent::setUp();

        $this->createSchemaForModels(
            GH6217AssociatedEntity::class,
            GH6217FetchedEntity::class
        );
    }

    public function testLoadingOfSecondLevelCacheOnEagerAssociations(): void
    {
        $lazy    = new GH6217AssociatedEntity();
        $eager   = new GH6217AssociatedEntity();
        $fetched = new GH6217FetchedEntity($lazy, $eager);

        $this->_em->persist($eager);
        $this->_em->persist($lazy);
        $this->_em->persist($fetched);
        $this->_em->flush();
        $this->_em->clear();

        $repository = $this->_em->getRepository(GH6217FetchedEntity::class);
        $filters    = ['eager' => $eager->id];

        self::assertCount(1, $repository->findBy($filters));
        $this->getQueryLog()->reset()->enable();

        /** @var GH6217FetchedEntity[] $found */
        $found = $repository->findBy($filters);

        self::assertCount(1, $found);
        self::assertInstanceOf(GH6217FetchedEntity::class, $found[0]);
        self::assertSame($lazy->id, $found[0]->lazy->id);
        self::assertSame($eager->id, $found[0]->eager->id);
        $this->assertQueryCount(0, 'No queries were executed in `findBy`');
    }
}

/**
 * @Entity
 * @Cache(usage="NONSTRICT_READ_WRITE")
 */
class GH6217AssociatedEntity
{
    /**
     * @var string
     * @Id
     * @Column(type="string", length=255)
     * @GeneratedValue(strategy="NONE")
     */
    public $id;

    public function __construct()
    {
        $this->id = uniqid(self::class, true);
    }
}

/**
 * @Entity
 * @Cache(usage="NONSTRICT_READ_WRITE")
 */
class GH6217FetchedEntity
{
    /**
     * @var GH6217AssociatedEntity
     * @Id
     * @Cache("NONSTRICT_READ_WRITE")
     * @ManyToOne(targetEntity=GH6217AssociatedEntity::class)
     */
    public $lazy;

    /**
     * @var GH6217AssociatedEntity
     * @Id
     * @Cache("NONSTRICT_READ_WRITE")
     * @ManyToOne(targetEntity=GH6217AssociatedEntity::class, fetch="EAGER")
     */
    public $eager;

    public function __construct(GH6217AssociatedEntity $lazy, GH6217AssociatedEntity $eager)
    {
        $this->lazy  = $lazy;
        $this->eager = $eager;
    }
}
