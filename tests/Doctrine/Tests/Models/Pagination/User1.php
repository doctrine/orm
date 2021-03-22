<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Pagination;

/**
 * @Entity()
 */
class User1 extends User
{
    /**
     * @var string
     * @Column(type="string")
     */
    public $email;
}
