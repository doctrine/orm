<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\DB2Platform;
use Doctrine\DBAL\Platforms\Exception\NotSupported;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Spatial\Geometry;
use Doctrine\DBAL\Spatial\SpatialRelation;
use Doctrine\Tests\Models\Spatial\Place;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use function enum_exists;
use function sprintf;

class QueryDqlSpatialFunctionTest extends OrmFunctionalTestCase
{
    private const ZONE = 'SRID=4326;POLYGON((-1 48,3 48,3 52,-1 52,-1 48))';

    protected function setUp(): void
    {
        parent::setUp();

        if (! enum_exists(SpatialRelation::class)) {
            self::markTestSkipped('The spatial functions require doctrine/dbal 4.6 or newer.');
        }

        $this->skipIfSpatialFunctionsAreNotSupported();

        $this->createSchemaForModels(Place::class);
        static::$sharedConn->executeStatement('DELETE FROM spatial_places');

        $this->_em->persist(new Place(
            1,
            'Paris',
            Geometry::point(2.3522, 48.8566, 4326),
            Geometry::fromWkt('SRID=4326;POLYGON((2.2 48.8,2.5 48.8,2.5 48.9,2.2 48.9,2.2 48.8))'),
        ));
        $this->_em->persist(new Place(2, 'London', Geometry::point(-0.1276, 51.5072, 4326)));
        $this->_em->persist(new Place(3, 'Berlin', Geometry::point(13.405, 52.52, 4326)));
        $this->_em->flush();
        $this->_em->clear();
    }

    /** @param list<string> $expectedNames */
    #[DataProvider('relationProvider')]
    public function testRelation(string $function, array $expectedNames): void
    {
        $names = $this->_em->createQuery(sprintf(
            'SELECT p.name FROM %s p WHERE %s(p.location, :zone) = true ORDER BY p.name',
            Place::class,
            $function,
        ))
            ->setParameter('zone', Geometry::fromWkt(self::ZONE))
            ->getSingleColumnResult();

        self::assertSame($expectedNames, $names);
    }

    /** @return iterable<array{string, list<string>}> */
    public static function relationProvider(): iterable
    {
        yield ['ST_CONTAINS', []];
        yield ['ST_CROSSES', []];
        yield ['ST_DISJOINT', ['Berlin']];
        yield ['ST_EQUALS', []];
        yield ['ST_INTERSECTS', ['London', 'Paris']];
        yield ['ST_OVERLAPS', []];
        yield ['ST_TOUCHES', []];
        yield ['ST_WITHIN', ['London', 'Paris']];
    }

    public function testRelationInSelectClause(): void
    {
        $result = $this->_em->createQuery(sprintf(
            'SELECT p.name, ST_INTERSECTS(p.location, :zone) AS inside FROM %s p ORDER BY p.id',
            Place::class,
        ))
            ->setParameter('zone', Geometry::fromWkt(self::ZONE))
            ->getArrayResult();

        self::assertSame([
            ['name' => 'Paris', 'inside' => true],
            ['name' => 'London', 'inside' => true],
            ['name' => 'Berlin', 'inside' => false],
        ], $result);
    }

    public function testWithinDistance(): void
    {
        $names = $this->_em->createQuery(sprintf(
            'SELECT p.name FROM %s p WHERE ST_DWITHIN(p.location, :paris, :distance) = true ORDER BY p.name',
            Place::class,
        ))
            ->setParameter('paris', Geometry::point(2.3522, 48.8566, 4326))
            ->setParameter('distance', 5)
            ->getSingleColumnResult();

        self::assertSame(['London', 'Paris'], $names);
    }

    public function testDistance(): void
    {
        $result = $this->_em->createQuery(sprintf(
            'SELECT p.name, ST_DISTANCE(p.location, :paris) AS distance FROM %s p ORDER BY distance',
            Place::class,
        ))
            ->setParameter('paris', Geometry::point(2.3522, 48.8566, 4326))
            ->getArrayResult();

        self::assertSame(['Paris', 'London', 'Berlin'], [$result[0]['name'], $result[1]['name'], $result[2]['name']]);
        self::assertIsFloat($result[1]['distance']);
        self::assertEqualsWithDelta(0.0, $result[0]['distance'], 1e-9);
        self::assertEqualsWithDelta(3.62975046, $result[1]['distance'], 1e-8);
        self::assertEqualsWithDelta(11.644092382, $result[2]['distance'], 1e-8);
    }

    public function testMeasuresAndCoordinates(): void
    {
        $result = $this->_em->createQuery(sprintf(
            'SELECT ST_X(p.location) AS x, ST_Y(p.location) AS y, ST_SRID(p.location) AS srid,'
                . ' ST_AREA(p.area) AS area, ST_LENGTH(:line) AS length FROM %s p WHERE p.id = 1',
            Place::class,
        ))
            ->setParameter('line', Geometry::fromWkt('SRID=4326;LINESTRING(0 0,3 4)'))
            ->getSingleResult();

        self::assertEqualsWithDelta(2.3522, $result['x'], 1e-9);
        self::assertEqualsWithDelta(48.8566, $result['y'], 1e-9);
        self::assertSame(4326, $result['srid']);
        self::assertEqualsWithDelta(0.03, $result['area'], 1e-9);
        self::assertEqualsWithDelta(5.0, $result['length'], 1e-9);
    }

    public function testGeodesicFunctions(): void
    {
        $platform = static::$sharedConn->getDatabasePlatform();

        // the geodesic functions of SpatiaLite read the ellipsoids from its spatial_ref_sys table
        if (
            $platform instanceof SQLitePlatform
            && static::$sharedConn->fetchOne("SELECT 1 FROM sqlite_master WHERE name = 'spatial_ref_sys'") === false
        ) {
            static::$sharedConn->executeQuery("SELECT InitSpatialMetadata(1, 'WGS84_ONLY')");
        }

        $names = $this->_em->createQuery(sprintf(
            'SELECT p.name FROM %s p WHERE ST_GEODESIC_DWITHIN(p.location, :paris, 350000) = true ORDER BY p.name',
            Place::class,
        ))
            ->setParameter('paris', Geometry::point(2.3522, 48.8566, 4326))
            ->getSingleColumnResult();

        self::assertSame(['London', 'Paris'], $names);

        $distance = $this->_em->createQuery(sprintf(
            'SELECT ST_GEODESIC_DISTANCE(p.location, :paris) FROM %s p WHERE p.id = 3',
            Place::class,
        ))
            ->setParameter('paris', Geometry::point(2.3522, 48.8566, 4326))
            ->getSingleScalarResult();

        // MySQL 5.7 and MariaDB compute on a sphere
        self::assertEqualsWithDelta(879699.316, $distance, 879699.316 * 0.005);

        if ($platform instanceof AbstractMySQLPlatform && ! $platform instanceof MySQL80Platform) {
            return;
        }

        $area = $this->_em->createQuery(sprintf(
            'SELECT ST_GEODESIC_AREA(p.area) FROM %s p WHERE p.id = 1',
            Place::class,
        ))->getSingleScalarResult();

        self::assertEqualsWithDelta(244847896.807, $area, 244847896.807 * 0.001);
    }

    public function testEntityHydration(): void
    {
        $place = $this->_em->find(Place::class, 1);

        self::assertNotNull($place);
        self::assertTrue($place->location->equals(Geometry::point(2.3522, 48.8566, 4326)));
        self::assertNotNull($place->area);
        self::assertSame(4326, $place->area->getSrid());
    }

    private function skipIfSpatialFunctionsAreNotSupported(): void
    {
        $connection = static::$sharedConn;
        $platform   = $connection->getDatabasePlatform();

        try {
            $platform->getGeometryTypeDeclarationSQL([]);
        } catch (NotSupported) {
            self::markTestSkipped(sprintf('%s does not support spatial types.', $platform::class));
        }

        if (
            $platform instanceof PostgreSQLPlatform
            && $connection->fetchOne("SELECT 1 FROM pg_extension WHERE extname = 'postgis'") === false
        ) {
            self::markTestSkipped('This test requires the PostGIS extension.');
        }

        if ($platform instanceof SQLitePlatform) {
            try {
                $connection->fetchOne('SELECT spatialite_version()');
            } catch (DriverException) {
                self::markTestSkipped('This test requires SpatiaLite.');
            }
        }

        if (
            ! $platform instanceof DB2Platform
            || $connection->fetchOne(
                "SELECT 1 FROM SYSCAT.DATATYPES WHERE TYPESCHEMA = 'SYSIBM' AND TYPENAME = 'ST_GEOMETRY'",
            ) !== false
        ) {
            return;
        }

        self::markTestSkipped('This test requires Db2 Spatial Analytics.');
    }
}
