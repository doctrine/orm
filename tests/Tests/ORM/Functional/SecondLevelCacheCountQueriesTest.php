<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Tests\Models\Cache\Country;
use ReflectionProperty;

use function array_diff;
use function array_filter;
use function file_exists;
use function rmdir;
use function scandir;
use function strpos;
use function sys_get_temp_dir;

use const DIRECTORY_SEPARATOR;
use const PHP_VERSION_ID;

/**
 * @group DDC-2183
 * @phpstan-type SupportedCacheUsage 0|ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE|ClassMetadata::CACHE_USAGE_READ_WRITE
 */
class SecondLevelCacheCountQueriesTest extends SecondLevelCacheFunctionalTestCase
{
    /** @var string */
    private $tmpDir;

    protected function tearDown(): void
    {
        if ($this->tmpDir !== null && file_exists($this->tmpDir)) {
            foreach (array_diff(scandir($this->tmpDir), ['.', '..']) as $f) {
                rmdir($this->tmpDir . DIRECTORY_SEPARATOR . $f);
            }

            rmdir($this->tmpDir);
        }

        parent::tearDown();
    }

    /** @param SupportedCacheUsage $cacheUsage */
    private function setupCountryModel(int $cacheUsage): void
    {
        $metadata = $this->_em->getClassMetaData(Country::class);

        if ($cacheUsage === 0) {
            $metadataCacheReflection = new ReflectionProperty(ClassMetadataInfo::class, 'cache');

            if (PHP_VERSION_ID < 80100) {
                $metadataCacheReflection->setAccessible(true);
            }

            $metadataCacheReflection->setValue($metadata, null);

            return;
        }

        if ($cacheUsage === ClassMetadata::CACHE_USAGE_READ_WRITE) {
            $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::class;
            $this->secondLevelCacheFactory->setFileLockRegionDirectory($this->tmpDir);
        }

        $metadata->enableCache(['usage' => $cacheUsage]);
    }

    private function loadFixturesCountriesWithoutPostInsertIdentifier(): void
    {
        $metadata = $this->_em->getClassMetaData(Country::class);
        $metadata->setIdGenerator(new AssignedGenerator());

        $c1 = new Country('Brazil');
        $c1->setId(10);
        $c2 = new Country('Germany');
        $c2->setId(20);

        $this->countries[] = $c1;
        $this->countries[] = $c2;

        $this->_em->persist($c1);
        $this->_em->persist($c2);
        $this->_em->flush();
    }

    /** @param 'INSERT'|'UPDATE'|'DELETE' $type */
    private function assertQueryCountByType(string $type, int $expectedCount): void
    {
        $queries = array_filter($this->getQueryLog()->queries, static function (array $entry) use ($type): bool {
            return strpos($entry['sql'], $type) === 0;
        });

        self::assertCount($expectedCount, $queries);
    }

    /**
     * @param SupportedCacheUsage $cacheUsage
     *
     * @dataProvider cacheUsageProvider
     */
    public function testInsertWithPostInsertIdentifier(int $cacheUsage): void
    {
        $this->setupCountryModel($cacheUsage);

        self::assertQueryCountByType('INSERT', 0);

        $this->loadFixturesCountries();

        self::assertCount(2, $this->countries);
        self::assertQueryCountByType('INSERT', 2);
    }

    /**
     * @param SupportedCacheUsage $cacheUsage
     *
     * @dataProvider cacheUsageProvider
     */
    public function testInsertWithoutPostInsertIdentifier(int $cacheUsage): void
    {
        $this->setupCountryModel($cacheUsage);

        self::assertQueryCountByType('INSERT', 0);

        $this->loadFixturesCountriesWithoutPostInsertIdentifier();

        self::assertCount(2, $this->countries);
        self::assertQueryCountByType('INSERT', 2);
    }

    /**
    /* @param SupportedCacheUsage $cacheUsage
     *
     * @dataProvider cacheUsageProvider
     */
    public function testDelete(int $cacheUsage): void
    {
        $this->setupCountryModel($cacheUsage);
        $this->loadFixturesCountries();

        $c1 = $this->_em->find(Country::class, $this->countries[0]->getId());
        $c2 = $this->_em->find(Country::class, $this->countries[1]->getId());

        $this->_em->remove($c1);
        $this->_em->remove($c2);
        $this->_em->flush();

        self::assertQueryCountByType('DELETE', 2);
    }

    /**
     * @param SupportedCacheUsage $cacheUsage
     *
     * @dataProvider cacheUsageProvider
     */
    public function testUpdate(int $cacheUsage): void
    {
        $this->setupCountryModel($cacheUsage);
        $this->loadFixturesCountries();

        $c1 = $this->_em->find(Country::class, $this->countries[0]->getId());
        $c2 = $this->_em->find(Country::class, $this->countries[1]->getId());

        $c1->setName('Czech Republic');
        $c2->setName('Hungary');

        $this->_em->persist($c1);
        $this->_em->persist($c2);
        $this->_em->flush();

        self::assertQueryCountByType('UPDATE', 2);
    }

    /** @return list<array{SupportedCacheUsage}> */
    public static function cacheUsageProvider(): array
    {
        return [
            [0],
            [ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE],
            [ClassMetadata::CACHE_USAGE_READ_WRITE],
        ];
    }
}
