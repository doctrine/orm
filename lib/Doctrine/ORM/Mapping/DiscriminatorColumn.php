<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class DiscriminatorColumn implements MappingAttribute
{
    /**
     * @var string|null
     * @readonly
     */
    public $name;

    /**
     * @var string|null
     * @readonly
     */
    public $type;

    /**
     * @var int|null
     * @readonly
     */
    public $length;

    /**
     * @var string|null
     * @readonly
     */
    public $columnDefinition;

    /**
     * @var class-string<\BackedEnum>|null
     * @readonly
     */
    public $enumType = null;

    /** @param class-string<\BackedEnum>|null $enumType */
    public function __construct(
        ?string $name = null,
        ?string $type = null,
        ?int $length = null,
        ?string $columnDefinition = null,
        ?string $enumType = null
    ) {
        $this->name             = $name;
        $this->type             = $type;
        $this->length           = $length;
        $this->columnDefinition = $columnDefinition;
        $this->enumType         = $enumType;
    }
}
