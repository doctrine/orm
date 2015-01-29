<?php

namespace Doctrine\Tests\Models\MixedToOneIdentity;

/** @Entity */
class CompositeToOneKeyState
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @Column(type="string")
     * @GeneratedValue(strategy="NONE")
     */
    public $state;

    /**
     * @Id
     * @ManyToOne(targetEntity="Country", cascade={"MERGE"})
     * @JoinColumn(referencedColumnName="country")
     */
    public $country;
}