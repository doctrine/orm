<?php

declare(strict_types=1);

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Models\Company;
use Doctrine\Tests\Models\Company\CompanyContractListener;

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
        "fix"       => Company\CompanyFixContract::class,
        "flexible"  => Company\CompanyFlexContract::class,
        "flexultra" => Company\CompanyFlexUltraContract::class
    ]
);

$fieldMetadata = new Mapping\FieldMetadata('id');
$fieldMetadata->setType(Type::getType('string'));
$fieldMetadata->setPrimaryKey(true);

$metadata->addProperty($fieldMetadata);

$fieldMetadata = new Mapping\FieldMetadata('completed');
$fieldMetadata->setType(Type::getType('boolean'));

$metadata->addProperty($fieldMetadata);

$metadata->addEntityListener(\Doctrine\ORM\Events::postPersist, CompanyContractListener::class, 'postPersistHandler');
$metadata->addEntityListener(\Doctrine\ORM\Events::prePersist, CompanyContractListener::class, 'prePersistHandler');

$metadata->addEntityListener(\Doctrine\ORM\Events::postUpdate, CompanyContractListener::class, 'postUpdateHandler');
$metadata->addEntityListener(\Doctrine\ORM\Events::preUpdate, CompanyContractListener::class, 'preUpdateHandler');

$metadata->addEntityListener(\Doctrine\ORM\Events::postRemove, CompanyContractListener::class, 'postRemoveHandler');
$metadata->addEntityListener(\Doctrine\ORM\Events::preRemove, CompanyContractListener::class, 'preRemoveHandler');

$metadata->addEntityListener(\Doctrine\ORM\Events::preFlush, CompanyContractListener::class, 'preFlushHandler');
$metadata->addEntityListener(\Doctrine\ORM\Events::postLoad, CompanyContractListener::class, 'postLoadHandler');
