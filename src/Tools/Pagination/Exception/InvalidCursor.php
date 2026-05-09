<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Pagination\Exception;

use InvalidArgumentException;
use Throwable;

use function sprintf;

final class InvalidCursor extends InvalidArgumentException
{
    public function __construct(string $cursor, Throwable|null $previous = null)
    {
        parent::__construct(
            sprintf(
                'The cursor "%s" could not be decoded.',
                $cursor,
            ),
            previous: $previous,
        );
    }
}
