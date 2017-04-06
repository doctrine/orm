<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;

$tableMetadata = new Mapping\TableMetadata();
$tableMetadata->setName('company_contracts');

/* @var $metadata ClassMetadata */
$metadata->setTable($tableMetadata);
$metadata->setInheritanceType(Mapping\InheritanceType::JOINED);

$discrColumn = new Mapping\DiscriminatorColumnMetadata();

$discrColumn->setColumnName('discr');
$discrColumn->setType(Type::getType('string'));

$metadata->setDiscriminatorColumn($discrColumn);

$metadata->setDiscriminatorMap(
    [
        "fix"       => "CompanyFixContract",
        "flexible"  => "CompanyFlexContract",
        "flexultra" => "CompanyFlexUltraContract"
    ]
);

$fieldMetadata = new Mapping\FieldMetadata('id');
$fieldMetadata->setType(Type::getType('string'));
$fieldMetadata->setPrimaryKey(true);

$metadata->addProperty($fieldMetadata);

$fieldMetadata = new Mapping\FieldMetadata('completed');
$fieldMetadata->setType(Type::getType('boolean'));

$metadata->addProperty($fieldMetadata);

$metadata->addEntityListener(\Doctrine\ORM\Events::postPersist, 'CompanyContractListener', 'postPersistHandler');
$metadata->addEntityListener(\Doctrine\ORM\Events::prePersist, 'CompanyContractListener', 'prePersistHandler');

$metadata->addEntityListener(\Doctrine\ORM\Events::postUpdate, 'CompanyContractListener', 'postUpdateHandler');
$metadata->addEntityListener(\Doctrine\ORM\Events::preUpdate, 'CompanyContractListener', 'preUpdateHandler');

$metadata->addEntityListener(\Doctrine\ORM\Events::postRemove, 'CompanyContractListener', 'postRemoveHandler');
$metadata->addEntityListener(\Doctrine\ORM\Events::preRemove, 'CompanyContractListener', 'preRemoveHandler');

$metadata->addEntityListener(\Doctrine\ORM\Events::preFlush, 'CompanyContractListener', 'preFlushHandler');
$metadata->addEntityListener(\Doctrine\ORM\Events::postLoad, 'CompanyContractListener', 'postLoadHandler');
