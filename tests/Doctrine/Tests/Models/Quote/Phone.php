<?php

namespace Doctrine\Tests\Models\Quote;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="quote-phone")
 */
class Phone
{
    /**
     * @ORM\Id
     * @ORM\Column(name="phone-number")
     */
    public $number;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="phones")
     * @ORM\JoinColumn(name="user-id", referencedColumnName="user-id")
     */
    public $user;
}
