<?php

namespace Doctrine\Tests\Models\DDC1872;

/**
 * @Entity
 *
 * @AttributeOverrides({
 * @AttributeOverride(name="foo",
 *          column=@Column(
 *              name     = "foo_overridden",
 *              type     = "integer",
 *              length   = 140,
 *              nullable = false,
 *              unique   = false
 *          )
 *      )
 * })
 *
 * @AssociationOverrides({
 * @AssociationOverride(name="bar",
 *          joinColumns=@JoinColumn(
 *              name="example_entity_overridden_bar_id", referencedColumnName="id"
 *          )
 *      )
 * })
 */
class DDC1872ExampleEntityWithOverride
{
    use DDC1872ExampleTrait;
}
