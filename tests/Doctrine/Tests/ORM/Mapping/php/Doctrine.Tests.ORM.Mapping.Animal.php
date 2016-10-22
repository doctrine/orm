<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;

/* @var ClassMetadata $metadata */
$discrColumn = new Mapping\DiscriminatorColumnMetadata();

$discrColumn->setTableName($metadata->getTableName());
$discrColumn->setColumnName('dtype');
$discrColumn->setType(Type::getType('string'));
$discrColumn->setLength(255);

$metadata->setDiscriminatorColumn($discrColumn);

$metadata->setDiscriminatorMap(array(
    'cat' => 'Doctrine\\Tests\\ORM\\Mapping\\Cat',
    'dog' => 'Doctrine\\Tests\\ORM\\Mapping\\Dog',
));

$metadata->setInheritanceType(Mapping\InheritanceType::NONE);
$metadata->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_DEFERRED_IMPLICIT);

$fieldMetadata = new Mapping\FieldMetadata('id');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setPrimaryKey(true);
$fieldMetadata->setNullable(false);
$fieldMetadata->setUnique(false);

$metadata->addProperty($fieldMetadata);

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_CUSTOM);

$metadata->setGeneratorDefinition(array(
    'class'     => 'stdClass',
    'arguments' => [],
));
