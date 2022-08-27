<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;

use function is_array;

/**
 * This annotation is used to override association mappings of relationship properties.
 *
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class AssociationOverrides implements Annotation
{
    /**
     * Mapping overrides of relationship properties.
     *
     * @var array<AssociationOverride>
     */
    public $overrides = [];

    /** @param array<AssociationOverride>|AssociationOverride $overrides */
    public function __construct($overrides)
    {
        if (! is_array($overrides)) {
            $overrides = [$overrides];
        }

        foreach ($overrides as $override) {
            if (! ($override instanceof AssociationOverride)) {
                throw MappingException::invalidOverrideType('AssociationOverride', $override);
            }

            $this->overrides[] = $override;
        }
    }
}
