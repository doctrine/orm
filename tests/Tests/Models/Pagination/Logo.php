<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Pagination;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

/**
 * Logo
 */
#[Table(name: 'pagination_logo')]
#[Entity]
class Logo
{
    /** @var int */
    #[Column(type: 'integer')]
    #[Id]
    #[GeneratedValue]
    public $id;

    /** @var string */
    #[Column(type: 'string', length: 255)]
    public $image;

    /** @var int */
    #[Column(type: 'integer')]
    public $imageHeight;

    /** @var int */
    #[Column(type: 'integer')]
    public $imageWidth;

    /** @var Company */
    #[OneToOne(targetEntity: 'Company', inversedBy: 'logo', cascade: ['persist'])]
    #[JoinColumn(name: 'company_id')]
    public $company;
}
