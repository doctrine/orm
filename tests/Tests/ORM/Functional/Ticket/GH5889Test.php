<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

use function array_keys;
use function glob;
use function is_dir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;

#[Group('GH-5889')]
class GH5889Test extends OrmFunctionalTestCase
{
    private string $lockDirectory;

    protected function setUp(): void
    {
        $this->enableSecondLevelCache();

        parent::setUp();

        $this->lockDirectory = sys_get_temp_dir() . '/doctrine-gh5889-' . uniqid();
        $this->secondLevelCacheFactory->setFileLockRegionDirectory($this->lockDirectory);
        $this->setUpEntitySchema([GH5889Page::class, GH5889Translation::class]);
    }

    protected function tearDown(): void
    {
        $connection = $this->_em->getConnection();
        $connection->executeStatement('DELETE FROM gh5889_selected_translations');
        $connection->executeStatement('DELETE FROM GH5889Translation');
        $connection->executeStatement('DELETE FROM GH5889Page');

        parent::tearDown();

        foreach (glob($this->lockDirectory . '/*') as $directory) {
            rmdir($directory);
        }

        if (is_dir($this->lockDirectory)) {
            rmdir($this->lockDirectory);
        }
    }

    /** @return iterable<string, array{string, int}> */
    public static function indexedAssociations(): iterable
    {
        foreach (['translations', 'selectedTranslations'] as $association) {
            yield $association . ' nonstrict' => [$association, ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE];
            yield $association . ' read-write' => [$association, ClassMetadata::CACHE_USAGE_READ_WRITE];
            yield $association . ' read-only' => [$association, ClassMetadata::CACHE_USAGE_READ_ONLY];
        }
    }

    #[DataProvider('indexedAssociations')]
    public function testIndexesAreBuiltOnReloadAfterInsert(string $association, int $usage): void
    {
        $page = $this->createPage($association, $usage);
        self::assertSame([0, 1], $page->{$association}->getKeys());

        $this->assertReloadedIndexes($association, ['en' => 1, 'pl' => 2]);
    }

    /** @return iterable<string, array{string, int, string}> */
    public static function mutations(): iterable
    {
        foreach (self::indexedAssociations() as $name => [$association, $usage]) {
            if ($usage === ClassMetadata::CACHE_USAGE_READ_ONLY) {
                continue;
            }

            foreach (['append', 'remove', 'clear'] as $operation) {
                yield $name . ' ' . $operation => [$association, $usage, $operation];
            }
        }
    }

    #[DataProvider('mutations')]
    public function testIndexesAreRebuiltAfterMutation(string $association, int $usage, string $operation): void
    {
        $this->createPage($association, $usage);
        $this->_em->getCache()->evictCollection(GH5889Page::class, $association, 1);
        $this->_em->clear();

        $page       = $this->_em->find(GH5889Page::class, 1);
        $collection = $page->{$association};
        self::assertCount(2, $collection);
        self::assertTrue($this->_em->getCache()->containsCollection(GH5889Page::class, $association, 1));

        $expected = ['en' => 1, 'pl' => 2];
        switch ($operation) {
            case 'append':
                $translation = new GH5889Translation(3, 'de', $association === 'translations' ? $page : null);
                $collection->add($translation);
                $this->_em->persist($translation);
                $expected['de'] = 3;
                break;

            case 'remove':
                $collection->remove('pl');
                unset($expected['pl']);
                break;

            case 'clear':
                $collection->clear();
                $expected = [];
                break;
        }

        $this->_em->flush();
        $this->assertReloadedIndexes($association, $expected);
    }

    private function createPage(string $association, int $usage): GH5889Page
    {
        $metadata = $this->_em->getClassMetadata(GH5889Page::class);

        $metadata->associationMappings[$association]->cache['usage'] = $usage;

        $this->_em->getCache()->evictEntityRegion(GH5889Page::class);
        $this->_em->getCache()->evictEntityRegion(GH5889Translation::class);
        $this->_em->getCache()->evictCollectionRegion(GH5889Page::class, $association);

        $page = new GH5889Page();
        foreach (['en', 'pl'] as $index => $language) {
            $translation = new GH5889Translation($index + 1, $language, $association === 'translations' ? $page : null);
            $page->{$association}->add($translation);
        }

        $this->_em->persist($page);
        $this->_em->flush();

        return $page;
    }

    /** @param array<string, int> $expected */
    private function assertReloadedIndexes(string $association, array $expected): void
    {
        $cache = $this->_em->getCache();
        $this->_em->clear();

        $page = $this->_em->find(GH5889Page::class, 1);
        $this->getQueryLog()->reset()->enable();
        $this->assertIndexes($page->{$association}, $expected);
        $this->assertQueryCount(1);
        self::assertTrue($cache->containsCollection(GH5889Page::class, $association, 1));
        $this->_em->clear();

        $page = $this->_em->find(GH5889Page::class, 1);
        $this->getQueryLog()->reset()->enable();
        $this->assertIndexes($page->{$association}, $expected);
        $this->assertQueryCount(0);
    }

    /**
     * @param Collection<string, GH5889Translation> $collection
     * @param array<string, int>                    $expected
     */
    private function assertIndexes(Collection $collection, array $expected): void
    {
        self::assertEqualsCanonicalizing($expected, $collection->map(static fn (GH5889Translation $translation): int => $translation->id)->toArray());
        self::assertEqualsCanonicalizing(array_keys($expected), $collection->getKeys());
        foreach ($expected as $language => $id) {
            self::assertSame($id, $collection->get($language)->id);
        }
    }
}

#[ORM\Entity]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class GH5889Page
{
    #[ORM\Id]
    #[ORM\Column]
    public int $id = 1;

    /** @var Collection<string, GH5889Translation> */
    #[ORM\OneToMany(targetEntity: GH5889Translation::class, mappedBy: 'page', cascade: ['persist'], orphanRemoval: true, indexBy: 'language')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    public Collection $translations;

    /** @var Collection<string, GH5889Translation> */
    #[ORM\ManyToMany(targetEntity: GH5889Translation::class, cascade: ['persist'], indexBy: 'language')]
    #[ORM\JoinTable(name: 'gh5889_selected_translations')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    public Collection $selectedTranslations;

    public function __construct()
    {
        $this->translations         = new ArrayCollection();
        $this->selectedTranslations = new ArrayCollection();
    }
}

#[ORM\Entity]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class GH5889Translation
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        public int $id,
        #[ORM\Column(length: 2)]
        public string $language,
        #[ORM\ManyToOne(targetEntity: GH5889Page::class, inversedBy: 'translations')]
        #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
        public GH5889Page|null $page,
    ) {
    }
}
