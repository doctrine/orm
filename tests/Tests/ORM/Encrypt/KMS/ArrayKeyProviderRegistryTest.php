<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Encrypt\KMS;

use Doctrine\ORM\Encrypt\KMS\ArrayKeyProviderRegistry;
use Doctrine\ORM\Encrypt\KMS\Exception\UnknownKeyProvider;
use Doctrine\Tests\Mocks\Encrypt\KMS\KeyProviderMock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArrayKeyProviderRegistry::class)]
final class ArrayKeyProviderRegistryTest extends TestCase
{
    /** @return iterable<string, array{string|null, string}> */
    public static function defaultNameProvider(): iterable
    {
        yield 'no default name given' => [null, 'a'];
        yield 'explicit default name' => ['b', 'b'];
    }

    #[DataProvider('defaultNameProvider')]
    public function testDefaultKeyProviderName(string|null $defaultArg, string $expectedName): void
    {
        $providers = ['a' => new KeyProviderMock('first'), 'b' => new KeyProviderMock('second')];

        $registry = $defaultArg === null
            ? new ArrayKeyProviderRegistry($providers)
            : new ArrayKeyProviderRegistry($providers, $defaultArg);

        self::assertSame($expectedName, $registry->getDefaultKeyProviderName());
        self::assertSame($providers[$expectedName], $registry->getKeyProvider());
    }

    public function testGetKeyProviderByName(): void
    {
        $first  = new KeyProviderMock('first');
        $second = new KeyProviderMock('second');

        $registry = new ArrayKeyProviderRegistry(['a' => $first, 'b' => $second]);

        self::assertSame($first, $registry->getKeyProvider('a'));
        self::assertSame($second, $registry->getKeyProvider('b'));
    }

    public function testConstructorUnknownDefaultName(): void
    {
        $this->expectException(UnknownKeyProvider::class);
        $this->expectExceptionMessage('Unknown key provider "missing". Known key providers: "a".');

        new ArrayKeyProviderRegistry(['a' => new KeyProviderMock('first')], 'missing');
    }

    public function testGetKeyProviderUnknownName(): void
    {
        $registry = new ArrayKeyProviderRegistry(['a' => new KeyProviderMock('first')]);

        $this->expectException(UnknownKeyProvider::class);
        $this->expectExceptionMessage('Unknown key provider "missing". Known key providers: "a".');

        $registry->getKeyProvider('missing');
    }

    public function testGetKeyProviderNames(): void
    {
        $registry = new ArrayKeyProviderRegistry([
            'a' => new KeyProviderMock('first'),
            'b' => new KeyProviderMock('second'),
        ]);

        self::assertSame(['a', 'b'], $registry->getKeyProviderNames());
    }

    public function testGetKeyProvidersReturns(): void
    {
        $first  = new KeyProviderMock('first');
        $second = new KeyProviderMock('second');

        $registry = new ArrayKeyProviderRegistry(['a' => $first, 'b' => $second]);

        self::assertSame(['a' => $first, 'b' => $second], $registry->getKeyProviders());
    }
}
