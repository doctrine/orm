<?php

namespace Doctrine\Tests\Models\Company;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;

/**
 * @Entity
 */
class CompanyFixContract extends CompanyContract
{
    /**
     * @column(type="integer")
     * @var int
     */
    private $fixPrice = 0;

    public function calculatePrice()
    {
        return $this->fixPrice;
    }

    public function getFixPrice()
    {
        return $this->fixPrice;
    }

    public function setFixPrice($fixPrice)
    {
        $this->fixPrice = $fixPrice;
    }

    static public function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadata $metadata)
    {
        $fieldMetadata = new Mapping\FieldMetadata('fixPrice');

        $fieldMetadata->setType(Type::getType('integer'));
        $fieldMetadata->setColumnName('fixPrice');

        $metadata->addProperty($fieldMetadata);
    }
}
