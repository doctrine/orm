<?php

namespace Doctrine\Tests\Models\Pagination;

use Doctrine\ORM\Annotation as ORM;

/**
 * Class User1
 *
 * @package Doctrine\Tests\Models\Pagination
 *
 * @ORM\Entity()
 */
class User1 extends User
{
    /**
     * @ORM\Column(type="string")
     */
    public $email;
}
