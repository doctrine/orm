<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;

use function array_values;
use function is_array;

/**
 * This attribute is used to override the mapping of a entity property.
 *
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class AttributeOverrides implements MappingAttribute
{
    /**
     * One or more field or property mapping overrides.
     *
     * @var list<AttributeOverride>
     * @readonly
     */
    public $overrides = [];

    /** @param array<AttributeOverride>|AttributeOverride $overrides */
    public function __construct($overrides)
    {
        if (! is_array($overrides)) {
            $overrides = [$overrides];
        }

        foreach ($overrides as $override) {
            if (! ($override instanceof AttributeOverride)) {
                throw MappingException::invalidOverrideType('AttributeOverride', $override);
            }
        }

        $this->overrides = array_values($overrides);
    }
}
