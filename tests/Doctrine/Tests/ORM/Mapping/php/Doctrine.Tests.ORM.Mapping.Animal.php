<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\ORM\Mapping\Cat;
use Doctrine\Tests\ORM\Mapping\Dog;

/* @var $metadata ClassMetadata */
$discrColumn = new Mapping\DiscriminatorColumnMetadata();
$discrColumn->setTableName($metadata->getTableName());
$discrColumn->setColumnName('dtype');
$discrColumn->setType(Type::getType('string'));
$discrColumn->setLength(255);

$metadata->setDiscriminatorColumn($discrColumn);

$metadata->setDiscriminatorMap(
    [
        'cat' => Cat::class,
        'dog' => Dog::class,
    ]
);

$metadata->setInheritanceType(Mapping\InheritanceType::NONE);
$metadata->setChangeTrackingPolicy(Mapping\ChangeTrackingPolicy::DEFERRED_IMPLICIT);

$fieldMetadata = new Mapping\FieldMetadata('id');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setPrimaryKey(true);
$fieldMetadata->setNullable(false);
$fieldMetadata->setUnique(false);

$metadata->addProperty($fieldMetadata);

$metadata->setIdGeneratorType(Mapping\GeneratorType::CUSTOM);

$metadata->setGeneratorDefinition(
    [
        'class'     => 'stdClass',
        'arguments' => [],
    ]
);
