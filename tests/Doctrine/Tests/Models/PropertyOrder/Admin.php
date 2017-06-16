<?php

namespace Doctrine\Tests\Models\PropertyOrder;

/**
 * @Entity
 */
class Admin extends User
{
    /**
     * @Column(type="string")
     */
    public $email;
}
