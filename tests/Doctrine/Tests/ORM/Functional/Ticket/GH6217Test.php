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
use PHPUnit\Framework\Attributes\Group;

use function uniqid;

#[Group('#6217')]
final class GH6217Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->enableSecondLevelCache();

        parent::setUp();

        $this->createSchemaForModels(
            GH6217AssociatedEntity::class,
            GH6217FetchedEntity::class,
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

#[Entity]
#[Cache(usage: 'NONSTRICT_READ_WRITE')]
class GH6217AssociatedEntity
{
    /** @var string */
    #[Id]
    #[Column(type: 'string', length: 255)]
    #[GeneratedValue(strategy: 'NONE')]
    public $id;

    public function __construct()
    {
        $this->id = uniqid(self::class, true);
    }
}

#[Entity]
#[Cache(usage: 'NONSTRICT_READ_WRITE')]
class GH6217FetchedEntity
{
    public function __construct(
        #[Id]
        #[Cache('NONSTRICT_READ_WRITE')]
        #[ManyToOne(targetEntity: GH6217AssociatedEntity::class)]
        public GH6217AssociatedEntity $lazy,
        #[Id]
        #[Cache('NONSTRICT_READ_WRITE')]
        #[ManyToOne(targetEntity: GH6217AssociatedEntity::class, fetch: 'EAGER')]
        public GH6217AssociatedEntity $eager,
    ) {
    }
}
