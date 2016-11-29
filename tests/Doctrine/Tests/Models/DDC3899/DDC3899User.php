<?php

namespace Doctrine\Tests\Models\DDC3899;

/**
 * @Entity
 * @Table(name="dc3899_users")
 */
class DDC3899User
{
    /** @Id @Column(type="integer") */
    public $id;

    /** @OneToMany(targetEntity="DDC3899Contract", mappedBy="user") */
    public $contracts;
}
