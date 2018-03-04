<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Exception;

use Doctrine\ORM\ORMException;

final class MissingColumnException extends \Exception implements ORMException
{
    public static function fromColumnSourceAndTarget(string $column, string $source, string $target) : self
    {
        return new self(sprintf(
            'Column name "%s" referenced for relation from %s towards %s does not exist.',
            $column,
            $source,
            $target
        ));
    }
}
