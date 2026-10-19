<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\EventListener\CacheMetadataListener;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('GH-11258')]
final class GH11258Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->enableSecondLevelCache();

        parent::setUp();

        // Preserve the deliberately uncached Bar when the full suite enables L2 globally.
        $eventManager = $this->_em->getEventManager();
        foreach ($eventManager->getListeners(Events::loadClassMetadata) as $listener) {
            if ($listener instanceof CacheMetadataListener) {
                $eventManager->removeEventListener([Events::loadClassMetadata], $listener);
            }
        }

        $this->setUpEntitySchema([GH11258Foo::class, GH11258Bar::class]);
    }

    protected function tearDown(): void
    {
        $this->_em->getConnection()->executeStatement('DELETE FROM GH11258Bar');
        $this->_em->getConnection()->executeStatement('DELETE FROM GH11258Foo');

        parent::tearDown();
    }

    /** @return iterable<string, array{string}> */
    public static function loadMethods(): iterable
    {
        yield 'findAll' => ['findAll'];
        yield 'findBy' => ['findBy'];
        yield 'findOneBy' => ['findOneBy'];
        yield 'cacheable fetch join' => ['query'];
    }

    #[DataProvider('loadMethods')]
    public function testUncachedAssociationIsLoadedFromTheDatabase(string $method): void
    {
        $foo      = new GH11258Foo();
        $bar      = new GH11258Bar();
        $foo->bar = $bar;
        $bar->foo = $foo;

        $this->_em->persist($foo);
        $this->_em->persist($bar);
        $this->_em->flush();
        $this->_em->clear();
        $this->_em->getCache()->evictEntityRegion(GH11258Foo::class);

        for ($i = 0; $i < 2; ++$i) {
            $loaded = $this->loadFoo($method);
            self::assertNotNull($loaded->bar);
            self::assertSame('original', $loaded->bar->name);
            self::assertSame($loaded, $loaded->bar->foo);
            self::assertTrue($this->_em->getCache()->containsEntity(GH11258Foo::class, 1));
            $this->_em->clear();
        }

        $this->_em->getConnection()->update('GH11258Bar', ['name' => 'updated'], ['id' => 1]);
        self::assertSame('updated', $this->loadFoo($method)->bar->name);
        $this->_em->clear();

        $this->_em->getConnection()->delete('GH11258Bar', ['id' => 1]);
        self::assertNull($this->loadFoo($method)->bar);
    }

    private function loadFoo(string $method): GH11258Foo
    {
        $repository = $this->_em->getRepository(GH11258Foo::class);

        return match ($method) {
            'findAll' => $repository->findAll()[0],
            'findBy' => $repository->findBy(['id' => 1])[0],
            'findOneBy' => $repository->findOneBy(['id' => 1]),
            'query' => $this->_em->createQuery('SELECT f, b FROM ' . GH11258Foo::class . ' f LEFT JOIN f.bar b')
                ->setCacheable(true)->getSingleResult(),
        };
    }
}

#[ORM\Entity]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class GH11258Foo
{
    #[ORM\Id]
    #[ORM\Column]
    public int $id = 1;

    #[ORM\OneToOne(targetEntity: GH11258Bar::class, mappedBy: 'foo', fetch: 'EXTRA_LAZY')]
    public GH11258Bar|null $bar = null;
}

#[ORM\Entity]
class GH11258Bar
{
    #[ORM\Id]
    #[ORM\Column]
    public int $id = 1;

    #[ORM\Column]
    public string $name = 'original';

    #[ORM\OneToOne(targetEntity: GH11258Foo::class, inversedBy: 'bar')]
    #[ORM\JoinColumn(nullable: false)]
    public GH11258Foo $foo;
}
