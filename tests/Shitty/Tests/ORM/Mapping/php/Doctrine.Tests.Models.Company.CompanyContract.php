<?php

use Shitty\ORM\Mapping\ClassMetadataInfo;

$metadata->setInheritanceType(ClassMetadataInfo::INHERITANCE_TYPE_JOINED);
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

$metadata->addEntityListener(\Shitty\ORM\Events::postPersist, 'CompanyContractListener', 'postPersistHandler');
$metadata->addEntityListener(\Shitty\ORM\Events::prePersist, 'CompanyContractListener', 'prePersistHandler');

$metadata->addEntityListener(\Shitty\ORM\Events::postUpdate, 'CompanyContractListener', 'postUpdateHandler');
$metadata->addEntityListener(\Shitty\ORM\Events::preUpdate, 'CompanyContractListener', 'preUpdateHandler');

$metadata->addEntityListener(\Shitty\ORM\Events::postRemove, 'CompanyContractListener', 'postRemoveHandler');
$metadata->addEntityListener(\Shitty\ORM\Events::preRemove, 'CompanyContractListener', 'preRemoveHandler');

$metadata->addEntityListener(\Shitty\ORM\Events::preFlush, 'CompanyContractListener', 'preFlushHandler');
$metadata->addEntityListener(\Shitty\ORM\Events::postLoad, 'CompanyContractListener', 'postLoadHandler');