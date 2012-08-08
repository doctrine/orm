<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->setInheritanceType(\Doctrine\ORM\Mapping\ClassMetadata::INHERITANCE_TYPE_JOINED);
$metadata->setTableName( 'company_contracts');
$metadata->setDiscriminatorColumn(array(
    'name' => 'discr',
    'type' => 'string',
));

$metadata->mapField(array(
    'id'        => true,
    'name'      => 'id',
    'fieldName' => 'id',
));

$metadata->mapField(array(
    'type'      => 'boolean',
    'name'      => 'completed',
    'fieldName' => 'completed',
));

$metadata->setDiscriminatorMap(array(
    "fix"       => "CompanyFixContract",
    "flexible"  => "CompanyFlexContract",
    "flexultra" => "CompanyFlexUltraContract"
));

$metadata->addEntityListener(\Doctrine\ORM\Events::postPersist, 'ContractSubscriber', 'postPersistHandler');
$metadata->addEntityListener(\Doctrine\ORM\Events::prePersist, 'ContractSubscriber', 'prePersistHandler');

$metadata->addEntityListener(\Doctrine\ORM\Events::postUpdate, 'ContractSubscriber', 'postUpdateHandler');
$metadata->addEntityListener(\Doctrine\ORM\Events::preUpdate, 'ContractSubscriber', 'preUpdateHandler');

$metadata->addEntityListener(\Doctrine\ORM\Events::postRemove, 'ContractSubscriber', 'postRemoveHandler');
$metadata->addEntityListener(\Doctrine\ORM\Events::preRemove, 'ContractSubscriber', 'preRemoveHandler');

$metadata->addEntityListener(\Doctrine\ORM\Events::preFlush, 'ContractSubscriber', 'preFlushHandler');
$metadata->addEntityListener(\Doctrine\ORM\Events::postLoad, 'ContractSubscriber', 'postLoadHandler');