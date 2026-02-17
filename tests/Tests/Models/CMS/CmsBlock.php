<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'cms_block')]
#[Entity]
class CmsBlock
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    public $id;

    /** @var Collection<self>  */
    #[OneToMany(targetEntity: CmsBlock::class, mappedBy: 'parent', orphanRemoval: true)]
    public $children;

    #[ManyToOne(targetEntity: CmsBlock::class, inversedBy: 'blocks')]
    public $parent;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }
}
