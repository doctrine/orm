<?php

namespace Doctrine\Tests\Models\DDC2042;

/**
 * @Entity
 *
 * @AssociationOverrides({
 * @AssociationOverride(name="bar",
 *          targetEntity="DDC2042Baz"
 *      )
 * })
 */
class DDC2042ExampleEntityWithOverride
{
    use DDC2042ExampleTrait;
}
