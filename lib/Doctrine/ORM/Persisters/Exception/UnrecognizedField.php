<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters\Exception;

use function sprintf;

final class UnrecognizedField extends \LogicException implements PersisterException
{
    public static function byName(string $field) : self
    {
        return new self(sprintf('Unrecognized field: %s', $field));
    }
}
