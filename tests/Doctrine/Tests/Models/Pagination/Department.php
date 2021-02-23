<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Pagination;

use Doctrine\ORM\Annotation as ORM;

/**
 * Department
 *
 * @ORM\Entity
 * @ORM\Table(name="pagination_department")
 */
class Department
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;

    /** @ORM\Column(type="string") */
    public $name;

    /**
     * @ORM\ManyToOne(
     *     targetEntity=Company::class,
     *     inversedBy="departments",
     *     cascade={"persist"}
     * )
     */
    public $company;
}
