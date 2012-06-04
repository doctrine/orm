<?php

namespace Doctrine\Tests\Models\Quote;

/**
 * @Entity
 * @Table(name="`quote-address`")
 */
class Address
{

    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer", name="`address-id`")
     */
    public $id;

    /**
     * @Column(name="`address-zip`")
     */
    public $zip;

    /**
     * @OneToOne(targetEntity="User", inversedBy="address")
     * @JoinColumn(name="`user-id`", referencedColumnName="`user-id`")
     */
    public $user;

}