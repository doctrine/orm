<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters\Exception;

use Doctrine\ORM\Exception\PersisterException;
use LogicException;

use function sprintf;

final class UnrecognizedField extends PersisterException
{
    public static function byName(string $field): self
    {
        return new self(sprintf('Unrecognized field: %s', $field));
    }
}
