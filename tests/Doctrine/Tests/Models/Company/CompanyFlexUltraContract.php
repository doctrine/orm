<?php

namespace Doctrine\Tests\Models\Company;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping;
use Doctrine\Tests\Models\Company\CompanyContractListener;
use Doctrine\Tests\Models\Company\CompanyFlexUltraContractListener;

/**
 * @ORM\Entity
 * @ORM\EntityListeners({"CompanyContractListener","CompanyFlexUltraContractListener"})
 */
class CompanyFlexUltraContract extends CompanyFlexContract
{
    /**
     * @ORM\Column(type="integer")
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

    static public function loadMetadata(Mapping\ClassMetadata $metadata)
    {
        $fieldMetadata = new Mapping\FieldMetadata('maxPrice');
        $fieldMetadata->setType(Type::getType('integer'));
        $fieldMetadata->setColumnName('maxPrice');

        $metadata->addProperty($fieldMetadata);

        $metadata->addEntityListener(\Doctrine\ORM\Events::postPersist, CompanyContractListener::class, 'postPersistHandler');
        $metadata->addEntityListener(\Doctrine\ORM\Events::prePersist, CompanyContractListener::class, 'prePersistHandler');

        $metadata->addEntityListener(\Doctrine\ORM\Events::postUpdate, CompanyContractListener::class, 'postUpdateHandler');
        $metadata->addEntityListener(\Doctrine\ORM\Events::preUpdate, CompanyContractListener::class, 'preUpdateHandler');

        $metadata->addEntityListener(\Doctrine\ORM\Events::postRemove, CompanyContractListener::class, 'postRemoveHandler');
        $metadata->addEntityListener(\Doctrine\ORM\Events::preRemove, CompanyContractListener::class, 'preRemoveHandler');

        $metadata->addEntityListener(\Doctrine\ORM\Events::preFlush, CompanyContractListener::class, 'preFlushHandler');
        $metadata->addEntityListener(\Doctrine\ORM\Events::postLoad, CompanyContractListener::class, 'postLoadHandler');

        $metadata->addEntityListener(\Doctrine\ORM\Events::prePersist, CompanyFlexUltraContractListener::class, 'prePersistHandler1');
        $metadata->addEntityListener(\Doctrine\ORM\Events::prePersist, CompanyFlexUltraContractListener::class, 'prePersistHandler2');
    }
}
