<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */
$fieldMetadata = new Mapping\FieldMetadata('maxPrice');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setColumnName('maxPrice');

$metadata->addProperty($fieldMetadata);

$metadata->addEntityListener(\Doctrine\ORM\Events::postPersist, 'CompanyContractListener', 'postPersistHandler');
$metadata->addEntityListener(\Doctrine\ORM\Events::prePersist, 'CompanyContractListener', 'prePersistHandler');

$metadata->addEntityListener(\Doctrine\ORM\Events::postUpdate, 'CompanyContractListener', 'postUpdateHandler');
$metadata->addEntityListener(\Doctrine\ORM\Events::preUpdate, 'CompanyContractListener', 'preUpdateHandler');

$metadata->addEntityListener(\Doctrine\ORM\Events::postRemove, 'CompanyContractListener', 'postRemoveHandler');
$metadata->addEntityListener(\Doctrine\ORM\Events::preRemove, 'CompanyContractListener', 'preRemoveHandler');

$metadata->addEntityListener(\Doctrine\ORM\Events::preFlush, 'CompanyContractListener', 'preFlushHandler');
$metadata->addEntityListener(\Doctrine\ORM\Events::postLoad, 'CompanyContractListener', 'postLoadHandler');

$metadata->addEntityListener(\Doctrine\ORM\Events::prePersist, 'CompanyFlexUltraContractListener', 'prePersistHandler1');
$metadata->addEntityListener(\Doctrine\ORM\Events::prePersist, 'CompanyFlexUltraContractListener', 'prePersistHandler2');
