<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Cache\CacheKeyCollisionChild;
use Doctrine\Tests\Models\Cache\CacheKeyCollisionOwner;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-2183')]
class SecondLevelCacheKeyCollisionTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->enableSecondLevelCache();

        parent::setUp();

        $this->createSchemaForModels(CacheKeyCollisionOwner::class, CacheKeyCollisionChild::class);
    }

    protected function tearDown(): void
    {
        $this->_em->getConnection()->executeStatement('DELETE FROM cache_key_collision_child');
        $this->_em->getConnection()->executeStatement('DELETE FROM cache_key_collision_owner');

        parent::tearDown();
    }

    /**
     * @param array{string, string} $firstId
     * @param array{string, string} $secondId
     */
    #[DataProvider('identifiers')]
    public function testFindReturnsTheCorrectCachedEntity(array $firstId, array $secondId): void
    {
        $this->persistOwner($firstId, 'first', 1);
        $this->persistOwner($secondId, 'second', 2);

        $this->getQueryLog()->reset()->enable();
        $first = $this->_em->find(CacheKeyCollisionOwner::class, ['first' => $firstId[0], 'second' => $firstId[1]]);
        self::assertNotNull($first);
        self::assertSame('first', $first->label);
        $this->_em->clear();

        $second = $this->_em->find(CacheKeyCollisionOwner::class, ['second' => $secondId[1], 'first' => $secondId[0]]);
        self::assertNotNull($second);
        self::assertSame('second', $second->label);
        $this->assertQueryCount(0);
    }

    /** @return iterable<string, array{array{string, string}, array{string, string}}> */
    public static function identifiers(): iterable
    {
        yield 'spaces between components' => [['a b', 'c'], ['a', 'b c']];
        yield 'reserved character' => [['a:b', 'c'], ['a_b', 'c']];
        yield 'backslash and dot' => [['a\\b', 'c'], ['a.b', 'c']];
    }

    public function testCollectionsAreIsolatedByOwnerIdentifier(): void
    {
        $this->persistOwner(['a b', 'c'], 'first', 1);
        $this->persistOwner(['a', 'b c'], 'second', 2);

        foreach ([['a b', 'c'], ['a', 'b c']] as [$first, $second]) {
            // Isolate collection key collisions from collisions in the owner's entity cache.
            $this->_em->getCache()->evictEntityRegion(CacheKeyCollisionOwner::class);
            $owner = $this->_em->find(CacheKeyCollisionOwner::class, ['first' => $first, 'second' => $second]);
            self::assertNotNull($owner);
            $owner->children->toArray();
            $this->_em->clear();
        }

        $this->_em->getCache()->evictEntityRegion(CacheKeyCollisionOwner::class);
        $owner = $this->_em->find(CacheKeyCollisionOwner::class, ['first' => 'a b', 'second' => 'c']);
        self::assertNotNull($owner);
        $this->getQueryLog()->reset()->enable();
        $children = $owner->children->toArray();
        self::assertCount(1, $children);
        self::assertSame('first', $children[0]->label);
        $this->assertQueryCount(0);
    }

    /** @param array{string, string} $identifier */
    private function persistOwner(array $identifier, string $label, int $childId): void
    {
        $owner = new CacheKeyCollisionOwner($identifier[0], $identifier[1], $label);
        $child = new CacheKeyCollisionChild($childId, $owner, $label);
        $owner->children->add($child);
        $this->_em->persist($owner);
        $this->_em->persist($child);
        $this->_em->flush();
        $this->_em->clear();
    }
}
