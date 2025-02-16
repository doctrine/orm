<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CompositeKeyRelations;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity]
class InvoiceClass
{
    #[Id]
    #[Column(type: 'string')]
    public string $companyCode;

    #[Id]
    #[Column(type: 'string')]
    public string $invoiceNumber;

    #[ManyToOne(targetEntity: CustomerClass::class)]
    #[JoinColumn(name: 'companyCode', referencedColumnName: 'companyCode')]
    #[JoinColumn(name: 'customerCode', referencedColumnName: 'code')]
    public CustomerClass|null $customer;

    #[Column(type: 'string', nullable: true)]
    public string|null $customerCode = null;
}
