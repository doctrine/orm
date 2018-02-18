<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\ORM\ORMException;

final class InvalidCustomGenerator extends \Exception implements ORMException
{
    public static function onClassNotConfigured() : self
    {
        return new self('Cannot instantiate custom generator, no class has been defined');
    }

    public static function onMissingClass(array $definition) : self
    {
        return new self(sprintf(
            'Cannot instantiate custom generator : %s',
            var_export($definition, true)
        ));
    }
}
