<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("PROPERTY")
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Embedded implements MappingAttribute
{
    /**
     * @var string|null
     * @readonly
     */
    public $class;

    /**
     * @var string|bool|null
     * @readonly
     */
    public $columnPrefix;

    public function __construct(?string $class = null, $columnPrefix = null)
    {
        $this->class        = $class;
        $this->columnPrefix = $columnPrefix;
    }
}
