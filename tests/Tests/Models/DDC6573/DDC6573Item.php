<?php

namespace Doctrine\Tests\Models\DDC6573;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'ddc6573_items')]
class DDC6573Item
{
    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue(strategy: 'AUTO')]
    public $id;

    #[Column(type: Types::STRING)]
    public $name;

    #[Column(type: Types::STRING)]
    public $priceAmount;

    #[Column(type: Types::STRING, length: 3)]
    public $priceCurrency;

    public function __construct(string $name, DDC6573Money $price)
    {
        $this->name = $name;
        $this->priceAmount = $price->getAmount();
        $this->priceCurrency = $price->getCurrency()->getCode();
    }

    public function getPrice(): DDC6573Money
    {
        return new DDC6573Money($this->priceAmount, new DDC6573Currency($this->priceCurrency));
    }
}
