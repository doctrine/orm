<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\CursorPagination;

use Doctrine\ORM\Tools\CursorPagination\Exception\InvalidCursor;
use JsonException;

use function base64_decode;
use function base64_encode;
use function json_decode;
use function json_encode;
use function rtrim;
use function strtr;

use const JSON_THROW_ON_ERROR;

/**
 * Represents a cursor for cursor-based pagination.
 *
 * A cursor contains the parameters needed to fetch the next or previous page of results.
 */
final class Cursor
{
    /** @param array<string, scalar> $parameters */
    public function __construct(
        private readonly array $parameters,
        private readonly bool $isNext = true,
    ) {
    }

    /**
     * @internal
     *
     * @return array<string, scalar>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Returns whether the cursor is for navigating to the next page.
     */
    public function isNext(): bool
    {
        return $this->isNext;
    }

    /**
     * Returns whether the cursor is for navigating to the previous page.
     */
    public function isPrevious(): bool
    {
        return ! $this->isNext;
    }

    /** @return array<string, scalar> */
    public function toArray(): array
    {
        return [...$this->parameters, '_isNext' => $this->isNext];
    }

    /**
     * Encodes the cursor to a URL-safe Base64 JSON string.
     */
    public function encodeToString(): string
    {
        return rtrim(strtr(base64_encode((string) json_encode($this->toArray())), '+/', '-_'), '=');
    }

    /**
     * Decodes a cursor from an encoded string.
     *
     * @see CursorWalker::buildCursorCondition() for the security model around cursor manipulation.
     *
     * @throws InvalidCursor If decoding fails.
     */
    public static function fromEncodedString(string $encodedString): self
    {
        $decoded = base64_decode(strtr($encodedString, '-_', '+/'), strict: true);

        if ($decoded === false) {
            throw new InvalidCursor($encodedString);
        }

        try {
            $parameters = json_decode($decoded, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidCursor($encodedString, $e);
        }

        $isNext = $parameters['_isNext'] ?? true;

        unset($parameters['_isNext']);

        return new self($parameters, $isNext);
    }
}
