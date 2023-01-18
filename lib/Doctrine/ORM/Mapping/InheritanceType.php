<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Deprecations\Deprecation;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class InheritanceType implements MappingAttribute
{
    /**
     * The inheritance type used by the class and its subclasses.
     *
     * @var string
     * @psalm-var 'NONE'|'JOINED'|'SINGLE_TABLE'|'TABLE_PER_CLASS'
     * @readonly
     * @Enum({"NONE", "JOINED", "SINGLE_TABLE", "TABLE_PER_CLASS"})
     */
    public $value;

    /** @psalm-param 'NONE'|'JOINED'|'SINGLE_TABLE'|'TABLE_PER_CLASS' $value */
    public function __construct(string $value)
    {
        if ($value === 'TABLE_PER_CLASS') {
            Deprecation::trigger(
                'doctrine/orm',
                'https://github.com/doctrine/orm/pull/10414/',
                'Concrete table inheritance has never been implemented, and its stubs will be removed in Doctrine ORM 3.0 with no replacement'
            );
        }

        $this->value = $value;
    }
}
