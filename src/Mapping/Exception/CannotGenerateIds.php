<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exception;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Exception\ORMException;

use function get_debug_type;
use function sprintf;

final class CannotGenerateIds extends ORMException
{
    public static function withPlatform(AbstractPlatform $platform): self
    {
        return new self(sprintf(
            'Platform %s does not support generating identifiers',
            get_debug_type($platform)
        ));
    }
}
