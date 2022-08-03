<?php

declare(strict_types=1);

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
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     */
    public $id;

    /**
     * @var bool
     * @Column(type="boolean")
     */
    public $completed = false;

    /**
     * @var DDC3899User
     * @ManyToOne(targetEntity="DDC3899User", inversedBy="contract")
     */
    public $user;
}
