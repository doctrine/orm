<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH10334;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity]
class GH10334Foo
{
    #[Id]
    #[ManyToOne(targetEntity: GH10334FooCollection::class, inversedBy: 'foos')]
    #[JoinColumn(name: 'foo_collection_id', referencedColumnName: 'id', nullable: false)]
    #[GeneratedValue]
    protected GH10334FooCollection $collection;

    #[Id]
    #[Column(type: 'string', enumType: 'Doctrine\Tests\Models\GH10334\GH10334ProductTypeId')]
    protected GH10334ProductTypeId $productTypeId;

    public function __construct(GH10334FooCollection $collection, GH10334ProductTypeId $productTypeId)
    {
        $this->collection    = $collection;
        $this->productTypeId = $productTypeId;
    }
}
