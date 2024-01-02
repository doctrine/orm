<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;

use function array_values;
use function is_array;

/** This attribute is used to override association mappings of relationship properties. */
#[Attribute(Attribute::TARGET_CLASS)]
final class AssociationOverrides implements MappingAttribute
{
    /**
     * Mapping overrides of relationship properties.
     *
     * @var list<AssociationOverride>
     */
    public readonly array $overrides;

    /** @param array<AssociationOverride>|AssociationOverride $overrides */
    public function __construct(array|AssociationOverride $overrides)
    {
        if (! is_array($overrides)) {
            $overrides = [$overrides];
        }

        foreach ($overrides as $override) {
            if (! ($override instanceof AssociationOverride)) {
                throw MappingException::invalidOverrideType('AssociationOverride', $override);
            }
        }

        $this->overrides = array_values($overrides);
    }
}
