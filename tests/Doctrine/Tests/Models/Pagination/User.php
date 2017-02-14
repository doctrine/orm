<?php

namespace Doctrine\Tests\Models\Pagination;

use Doctrine\ORM\Annotation as ORM;

/**
 * @package Doctrine\Tests\Models\Pagination
 *
 * @ORM\Entity
 * @ORM\Table(name="pagination_user")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"user1"="User1"})
 */
abstract class User
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    public $name;
}
