<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\Tests\Models\Enums\Scale;
use Doctrine\Tests\Models\Enums\Unit;
use Doctrine\Tests\OrmFunctionalTestCase;

use function explode;
use function is_resource;
use function json_decode;
use function method_exists;
use function sprintf;
use function stream_get_contents;
use function substr;

use const JSON_THROW_ON_ERROR;

class QueryDqlStringToArrayFunctionTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! method_exists(AbstractPlatform::class, 'getStringToArrayExpression')) {
            self::markTestSkipped('STRING_TO_ARRAY() requires doctrine/dbal 4.6 or newer.');
        }

        $this->createSchemaForModels(Scale::class);
        $this->_em->createQuery('DELETE FROM ' . Scale::class . ' s')->execute();

        $scale                 = new Scale();
        $scale->supportedUnits = [Unit::Gram, Unit::Meter];

        $this->_em->persist($scale);
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testSplitSimpleArrayField(): void
    {
        $units = $this->_em->createQuery(sprintf(
            "SELECT STRING_TO_ARRAY(s.supportedUnits, ',') FROM %s s",
            Scale::class,
        ))->getSingleScalarResult();

        self::assertSame(['g', 'm'], $this->decode($units));
    }

    public function testSplitParameters(): void
    {
        $array = $this->_em->createQuery(sprintf(
            'SELECT STRING_TO_ARRAY(:string, :delimiter) FROM %s s',
            Scale::class,
        ))
            ->setParameter('string', 'a~~b~~c')
            ->setParameter('delimiter', '~~')
            ->getSingleScalarResult();

        self::assertSame(['a', 'b', 'c'], $this->decode($array));
    }

    /**
     * Decodes the PostgreSQL array or the JSON array of strings without special characters.
     *
     * @return list<string>
     */
    private function decode(mixed $array): array
    {
        if (is_resource($array)) {
            $array = stream_get_contents($array);
        }

        self::assertIsString($array);

        if ($this->_em->getConnection()->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            return explode(',', substr($array, 1, -1));
        }

        $decoded = json_decode($array, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }
}
