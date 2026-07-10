<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Encrypt\EncryptQuery;
use Doctrine\ORM\Mapping\Encrypt;
use Doctrine\ORM\Mapping\EncryptMapping;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function serialize;
use function unserialize;

final class EncryptMappingTest extends TestCase
{
    public function testFromAttribute(): void
    {
        $mapping = EncryptMapping::fromAttribute(new Encrypt(
            cipher: 'default_cipher',
            keyProvider: 'default_key_provider',
            queryType: EncryptQuery::Equality,
        ));

        self::assertSame('default_cipher', $mapping->cipher);
        self::assertSame('default_key_provider', $mapping->keyProvider);
        self::assertSame(EncryptQuery::Equality, $mapping->queryType);
    }

    /** @return iterable<string, array{array<string, string>, string|null, string|null, EncryptQuery|null}> */
    public static function mappingArrayProvider(): iterable
    {
        yield 'all keys present' => [
            [
                'cipher' => 'default_cipher',
                'keyProvider' => 'default_key_provider',
                'queryType' => 'equality',
            ],
            'default_cipher',
            'default_key_provider',
            EncryptQuery::Equality,
        ];

        yield 'missing keys default to null' => [[], null, null, null];
    }

    /** @param array<string, string> $in */
    #[DataProvider('mappingArrayProvider')]
    public function testFromMappingArray(
        array $in,
        string|null $expectedCipher,
        string|null $expectedKeyProvider,
        EncryptQuery|null $expectedQueryType,
    ): void {
        $mapping = EncryptMapping::fromMappingArray($in);

        self::assertSame($expectedCipher, $mapping->cipher);
        self::assertSame($expectedKeyProvider, $mapping->keyProvider);
        self::assertSame($expectedQueryType, $mapping->queryType);
    }

    public function testToArray(): void
    {
        $mapping = EncryptMapping::fromMappingArray([
            'cipher' => 'default_cipher',
            'keyProvider' => 'default_key_provider',
            'queryType' => 'equality',
        ]);

        self::assertSame([
            'cipher' => 'default_cipher',
            'keyProvider' => 'default_key_provider',
            'queryType' => EncryptQuery::Equality,
        ], (array) $mapping);
    }

    public function testItSurvivesSerialization(): void
    {
        $mapping              = new EncryptMapping();
        $mapping->cipher      = 'default_cipher';
        $mapping->keyProvider = 'default_key_provider';
        $mapping->queryType   = EncryptQuery::Equality;

        $resurrectedMapping = unserialize(serialize($mapping));

        self::assertInstanceOf(EncryptMapping::class, $resurrectedMapping);

        self::assertSame('default_cipher', $resurrectedMapping->cipher);
        self::assertSame('default_key_provider', $resurrectedMapping->keyProvider);
        self::assertSame(EncryptQuery::Equality, $resurrectedMapping->queryType);
    }
}
