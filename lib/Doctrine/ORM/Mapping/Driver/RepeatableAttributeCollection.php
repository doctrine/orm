<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver;

use ArrayObject;
use Doctrine\ORM\Mapping\Annotation;

/**
 * @template-extends ArrayObject<int,Annotation>
 */
final class RepeatableAttributeCollection extends ArrayObject
{
}
