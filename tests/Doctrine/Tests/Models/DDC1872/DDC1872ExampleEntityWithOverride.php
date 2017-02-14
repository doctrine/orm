<?php

namespace Doctrine\Tests\Models\DDC1872;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 *
 * @ORM\AttributeOverrides({
 * @ORM\AttributeOverride(name="foo",
 *          column=@ORM\Column(
 *              name     = "foo_overridden",
 *              type     = "integer",
 *              length   = 140,
 *              nullable = false,
 *              unique   = false
 *          )
 *      )
 * })
 *
 * @ORM\AssociationOverrides({
 * @ORM\AssociationOverride(name="bar",
 *          joinColumns=@ORM\JoinColumn(
 *              name="example_entity_overridden_bar_id", referencedColumnName="id"
 *          )
 *      )
 * })
 */
class DDC1872ExampleEntityWithOverride
{
    use DDC1872ExampleTrait;
}
