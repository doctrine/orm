<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC4006;

/**
 * @Embeddable
 */
class DDC4006UserId
{
    /**
     * @var int
     * @Id
     * @GeneratedValue("IDENTITY")
     * @Column(type="integer")
     */
    private $id;
}
