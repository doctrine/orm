<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\MixedToOneIdentity;

/** @Entity */
class CompositeToOneKeyState
{
    /**
     * @var string
     * @Id
     * @Column(type="string")
     * @GeneratedValue(strategy="NONE")
     */
    public $state;

    /**
     * @var Country
     * @Id
     * @ManyToOne(targetEntity="Country", cascade={"MERGE"})
     * @JoinColumn(referencedColumnName="country")
     */
    public $country;
}
