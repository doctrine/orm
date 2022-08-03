<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Quote;

/**
 * @Entity
 * @Table(name="`quote-phone`")
 */
class Phone
{
    /**
     * @var string
     * @Id
     * @Column(name="`phone-number`")
     */
    public $number;

    /**
     * @var User
     * @ManyToOne(targetEntity="User", inversedBy="phones")
     * @JoinColumn(name="`user-id`", referencedColumnName="`user-id`")
     */
    public $user;
}
