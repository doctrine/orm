<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

/**
 * Description of CmsEmployee
 */
#[Table(name: 'cms_employees')]
#[Entity]
class CmsEmployee
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    private int $id;

    #[Column]
    private string $name;

    #[OneToOne(targetEntity: 'CmsEmployee')]
    #[JoinColumn(name: 'spouse_id', referencedColumnName: 'id')]
    private CmsEmployee $spouse;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSpouse(): CmsEmployee|null
    {
        return $this->spouse;
    }
}
