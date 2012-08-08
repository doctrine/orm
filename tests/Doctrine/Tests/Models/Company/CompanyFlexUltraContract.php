<?php

namespace Doctrine\Tests\Models\Company;

/**
 * @Entity
 * @EntityListeners({"ContractSubscriber","FlexUltraContractSubscriber"})
 */
class CompanyFlexUltraContract extends CompanyFlexContract
{
    /**
     * @column(type="integer")
     * @var int
     */
    private $maxPrice = 0;

    public function calculatePrice()
    {
        return max($this->maxPrice, parent::calculatePrice());
    }

    public function getMaxPrice()
    {
        return $this->maxPrice;
    }

    public function setMaxPrice($maxPrice)
    {
        $this->maxPrice = $maxPrice;
    }

    static public function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadataInfo $metadata)
    {
        $metadata->mapField(array(
            'type'      => 'integer',
            'name'      => 'maxPrice',
            'fieldName' => 'maxPrice',
        ));
        $metadata->addEntityListener(\Doctrine\ORM\Events::postPersist, 'ContractSubscriber', 'postPersistHandler');
        $metadata->addEntityListener(\Doctrine\ORM\Events::prePersist, 'ContractSubscriber', 'prePersistHandler');

        $metadata->addEntityListener(\Doctrine\ORM\Events::postUpdate, 'ContractSubscriber', 'postUpdateHandler');
        $metadata->addEntityListener(\Doctrine\ORM\Events::preUpdate, 'ContractSubscriber', 'preUpdateHandler');

        $metadata->addEntityListener(\Doctrine\ORM\Events::postRemove, 'ContractSubscriber', 'postRemoveHandler');
        $metadata->addEntityListener(\Doctrine\ORM\Events::preRemove, 'ContractSubscriber', 'preRemoveHandler');

        $metadata->addEntityListener(\Doctrine\ORM\Events::preFlush, 'ContractSubscriber', 'preFlushHandler');
        $metadata->addEntityListener(\Doctrine\ORM\Events::postLoad, 'ContractSubscriber', 'postLoadHandler');
        
        $metadata->addEntityListener(\Doctrine\ORM\Events::prePersist, 'FlexUltraContractSubscriber', 'prePersistHandler1');
        $metadata->addEntityListener(\Doctrine\ORM\Events::prePersist, 'FlexUltraContractSubscriber', 'prePersistHandler2');
    }
}