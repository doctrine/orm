<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use LogicException;

use function get_debug_type;

final class InstanceOfTheWrongTypeEncountered extends LogicException implements ORMException
{
    /** @param object $instance */
    public static function forInstance($instance): self
    {
        return new self('Instance of the wrong type (' . get_debug_type($instance) . ') was retrieved in inheritance hierachy.' .
                        'This happens because identity map aggregates instances by rootEntityName ');
    }
}
