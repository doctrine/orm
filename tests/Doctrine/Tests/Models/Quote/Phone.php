<?php

namespace Doctrine\Tests\Models\Quote;

/**
 * @Entity
 * @Table(name="`quote-phone`")
 */
class Phone
{

    /**
     * @Id
     * @Column(name="`phone-number`")
     */
    public $number;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="phones")
     * @JoinColumn(name="`user-id`", referencedColumnName="`user-id`")
     */
    public $user;

}
