<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target({"PROPERTY","ANNOTATION"})
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class JoinColumn implements Annotation
{
    /**
     * @param string|null          $fieldName Field name used in non-object hydration (array/scalar).
     * @param array<string, mixed> $options
     */
    public function __construct(
        public string|null $name = null,
        public string $referencedColumnName = 'id',
        public bool $unique = false,
        public bool $nullable = true,
        public mixed $onDelete = null,
        public string|null $columnDefinition = null,
        public string|null $fieldName = null,
        public array $options = [],
    ) {
    }
}
