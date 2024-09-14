<?php

declare(strict_types=1);

use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;

$metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_JOINED);
$metadata->setTableName('company_contracts');
$metadata->setDiscriminatorColumn(
    [
        'name' => 'discr',
        'type' => 'string',
    ]
);

$metadata->mapField(
    [
        'id'        => true,
        'name'      => 'id',
        'fieldName' => 'id',
    ]
);

$metadata->mapField(
    [
        'type'      => 'boolean',
        'name'      => 'completed',
        'fieldName' => 'completed',
    ]
);

$metadata->setDiscriminatorMap(
    [
        'fix'       => 'CompanyFixContract',
        'flexible'  => 'CompanyFlexContract',
        'flexultra' => 'CompanyFlexUltraContract',
    ]
);

$metadata->addEntityListener(Events::postPersist, 'CompanyContractListener', 'postPersistHandler');
$metadata->addEntityListener(Events::prePersist, 'CompanyContractListener', 'prePersistHandler');

$metadata->addEntityListener(Events::postUpdate, 'CompanyContractListener', 'postUpdateHandler');
$metadata->addEntityListener(Events::preUpdate, 'CompanyContractListener', 'preUpdateHandler');

$metadata->addEntityListener(Events::postRemove, 'CompanyContractListener', 'postRemoveHandler');
$metadata->addEntityListener(Events::preRemove, 'CompanyContractListener', 'preRemoveHandler');

$metadata->addEntityListener(Events::preFlush, 'CompanyContractListener', 'preFlushHandler');
$metadata->addEntityListener(Events::postLoad, 'CompanyContractListener', 'postLoadHandler');
