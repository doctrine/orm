<?php

namespace Doctrine\Tests\Models\Company;

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

    static public function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadataInfo $metadata)
    {
        $metadata->mapField(array(
            'type'      => 'integer',
            'name'      => 'fixPrice',
            'fieldName' => 'fixPrice',
        ));
    }
}