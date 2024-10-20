<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Pagination;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

/**
 * Department
 */
#[Table(name: 'pagination_department')]
#[Entity]
class Department
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @var string */
    #[Column(type: 'string', length: 255)]
    public $name;

    /** @var Company */
    #[ManyToOne(targetEntity: 'Company', inversedBy: 'departments', cascade: ['persist'])]
    public $company;
}
