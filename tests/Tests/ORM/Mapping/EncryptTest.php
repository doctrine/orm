<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Encrypt\EncryptQuery;
use Doctrine\ORM\Mapping\Encrypt;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EncryptTest extends TestCase
{
    public function testDefaults(): void
    {
        $encrypt = new Encrypt();

        self::assertNull($encrypt->cipher);
        self::assertNull($encrypt->keyProvider);
        self::assertNull($encrypt->queryType);
    }

    /** @return iterable<string, array{Encrypt, array<string, string|null>}> */
    public static function toArrayProvider(): iterable
    {
        yield 'explicit values' => [
            new Encrypt(
                cipher: 'default_cipher',
                keyProvider: 'default_key_provider',
                queryType: EncryptQuery::Equality,
            ),
            [
                'cipher' => 'default_cipher',
                'keyProvider' => 'default_key_provider',
                'queryType' => EncryptQuery::Equality,
            ],
        ];

        yield 'defaults' => [
            new Encrypt(),
            [
                'cipher' => null,
                'keyProvider' => null,
                'queryType' => null,
            ],
        ];
    }

    /** @param array<string, string|null> $expected */
    #[DataProvider('toArrayProvider')]
    public function testCastArray(Encrypt $attribute, array $expected): void
    {
        self::assertSame($expected, (array) $attribute);
    }
}
