<?php

namespace Doctrine\Tests\Models\DDC3899;

/**
 * @Entity
 * @Table(name="dc3899_contracts")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *     "fix"       = "DDC3899FixContract",
 *     "flexible"  = "DDC3899FlexContract"
 * })
 */
abstract class DDC3899Contract
{
    /** @Id @Column(type="integer") */
    public $id;

    /** @Column(type="boolean") */
    public $completed = false;

    /** @ManyToOne(targetEntity="DDC3899User", inversedBy="contract") */
    public $user;
}
