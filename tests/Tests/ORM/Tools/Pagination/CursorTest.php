<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Pagination;

use Doctrine\ORM\Tools\Pagination\Cursor;
use Doctrine\ORM\Tools\Pagination\Exception\InvalidCursor;
use JsonException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function base64_encode;
use function json_encode;
use function rtrim;
use function strtr;

class CursorTest extends TestCase
{
    public function testCursorIsFinal(): void
    {
        $reflection = new ReflectionClass(Cursor::class);

        self::assertTrue($reflection->isFinal());
    }

    public function testEncodeAndDecode(): void
    {
        $parameters = ['id' => 10, 'name' => 'test'];
        $cursor     = new Cursor($parameters, true);

        $decoded = Cursor::fromEncodedString($cursor->encodeToString());

        self::assertSame($parameters, $decoded->getParameters());
        self::assertTrue($decoded->isNext());
    }

    public function testEncodeAndDecodeWithPreviousCursor(): void
    {
        $cursor = new Cursor(['id' => 10], false);

        $decoded = Cursor::fromEncodedString($cursor->encodeToString());

        self::assertTrue($decoded->isPrevious());
    }

    public function testEncodeProducesUrlSafeString(): void
    {
        $cursor  = new Cursor(['id' => 123456789, 'foo' => 'bar/baz+test']);
        $encoded = $cursor->encodeToString();

        self::assertStringNotContainsString('+', $encoded);
        self::assertStringNotContainsString('/', $encoded);
        self::assertStringNotContainsString('=', $encoded);
    }

    public function testFromEncodedStringThrowsForInvalidBase64(): void
    {
        $this->expectException(InvalidCursor::class);

        Cursor::fromEncodedString('not-valid-base64!@#$');
    }

    public function testFromEncodedStringThrowsForInvalidJson(): void
    {
        $this->expectException(InvalidCursor::class);

        Cursor::fromEncodedString(rtrim(strtr(base64_encode('not-json'), '+/', '-_'), '='));
    }

    public function testFromEncodedStringChainsPreviousExceptionForInvalidJson(): void
    {
        try {
            Cursor::fromEncodedString(rtrim(strtr(base64_encode('not-json'), '+/', '-_'), '='));
            self::fail('Expected InvalidCursor to be thrown.');
        } catch (InvalidCursor $e) {
            self::assertInstanceOf(JsonException::class, $e->getPrevious());
        }
    }

    public function testFromEncodedStringDefaultsToNextWhenIsNextMissing(): void
    {
        $json = json_encode(['id' => 10]);
        self::assertIsString($json);
        $encoded = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');

        $cursor = Cursor::fromEncodedString($encoded);

        self::assertTrue($cursor->isNext());
    }

    public function testToArray(): void
    {
        $cursor = new Cursor(['id' => 1], true);

        self::assertSame(['id' => 1, '_isNext' => true], $cursor->toArray());
    }

    public function testFromEncodedStringThrowsForNonStringInput(): void
    {
        $this->expectException(InvalidCursor::class);

        Cursor::fromEncodedString(['injected' => 'array']);
    }

    public function testFromEncodedStringThrowsForNonScalarParameterValue(): void
    {
        $payload = rtrim(strtr(base64_encode((string) json_encode(['p.id' => [1, 2, 3], '_isNext' => true])), '+/', '-_'), '=');
        $this->expectException(InvalidCursor::class);
        Cursor::fromEncodedString($payload);
    }

    public function testFromEncodedStringCastsIsNextToBool(): void
    {
        $payload = rtrim(strtr(base64_encode((string) json_encode(['id' => 10, '_isNext' => 0])), '+/', '-_'), '=');
        $cursor  = Cursor::fromEncodedString($payload);

        self::assertFalse($cursor->isNext());
        self::assertTrue($cursor->isPrevious());
    }

    public function testFromEncodedStringThrowsForNonArrayJson(): void
    {
        $payload = rtrim(strtr(base64_encode((string) json_encode('just-a-string')), '+/', '-_'), '=');
        $this->expectException(InvalidCursor::class);
        Cursor::fromEncodedString($payload);
    }
}
