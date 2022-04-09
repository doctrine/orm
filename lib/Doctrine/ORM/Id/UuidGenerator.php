<?php

declare(strict_types=1);

namespace Doctrine\ORM\Id;

use Doctrine\ORM\Exception\NotSupported;

use function sprintf;

/**
 * Represents an ID generator that uses the database UUID expression
 *
 * @deprecated use an application-side generator instead
 */
class UuidGenerator extends AbstractIdGenerator
{
    public function __construct()
    {
        throw NotSupported::createForDbal3(sprintf(
            'Using the database to generate a UUID through %s',
            self::class
        ));
    }
}
