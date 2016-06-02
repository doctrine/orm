<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_JOINED);
$metadata->setPrimaryTable(['name' => 'company_contracts']);

$metadata->setDiscriminatorColumn(
    [
        'name' => 'discr',
        'type' => 'string',
    ]
);

$metadata->setDiscriminatorMap(
    [
        "fix"       => "CompanyFixContract",
        "flexible"  => "CompanyFlexContract",
        "flexultra" => "CompanyFlexUltraContract"
    ]
);

$metadata->addProperty('id', Type::getType('string'), ['id' => true]);
$metadata->addProperty('completed', Type::getType('boolean'));

$metadata->addEntityListener(\Doctrine\ORM\Events::postPersist, 'CompanyContractListener', 'postPersistHandler');
$metadata->addEntityListener(\Doctrine\ORM\Events::prePersist, 'CompanyContractListener', 'prePersistHandler');

$metadata->addEntityListener(\Doctrine\ORM\Events::postUpdate, 'CompanyContractListener', 'postUpdateHandler');
$metadata->addEntityListener(\Doctrine\ORM\Events::preUpdate, 'CompanyContractListener', 'preUpdateHandler');

$metadata->addEntityListener(\Doctrine\ORM\Events::postRemove, 'CompanyContractListener', 'postRemoveHandler');
$metadata->addEntityListener(\Doctrine\ORM\Events::preRemove, 'CompanyContractListener', 'preRemoveHandler');

$metadata->addEntityListener(\Doctrine\ORM\Events::preFlush, 'CompanyContractListener', 'preFlushHandler');
$metadata->addEntityListener(\Doctrine\ORM\Events::postLoad, 'CompanyContractListener', 'postLoadHandler');
