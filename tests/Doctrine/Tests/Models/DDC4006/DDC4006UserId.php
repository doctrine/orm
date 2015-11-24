<?php

namespace Doctrine\Tests\Models\DDC4006;

/**
 * @Embeddable
 */
class DDC4006UserId
{
    /**
     * @Id
     * @GeneratedValue("IDENTITY")
     * @Column(type="integer")
     */
    private $id;
}
