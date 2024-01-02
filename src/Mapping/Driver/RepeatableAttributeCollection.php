<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver;

use ArrayObject;
use Doctrine\ORM\Mapping\MappingAttribute;

/**
 * @template-extends ArrayObject<int, T>
 * @template T of MappingAttribute
 */
final class RepeatableAttributeCollection extends ArrayObject
{
}
