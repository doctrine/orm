<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CompositeKeyRelations;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;

#[Entity]
class CustomerClass
{
    #[Id]
    #[Column(type: 'string')]
    public string $companyCode;

    #[Id]
    #[Column(type: 'string')]
    public string $code;

    /** @var Collection<int, InvoiceClass> */
    #[OneToMany(targetEntity: InvoiceClass::class, mappedBy: 'customer')]
    public Collection $invoices;

    #[Column(type: 'string')]
    public string $name;
}
