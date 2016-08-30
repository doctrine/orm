<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;

/* @var ClassMetadata $metadata */
$metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE);

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

$metadata->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_DEFERRED_IMPLICIT);

$metadata->addProperty('id', Type::getType('integer'), array(
    'length'   => NULL,
    'nullable' => false,
    'unique'   => false,
    'id'       => true,
));

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_CUSTOM);

$metadata->setGeneratorDefinition(array(
    'class'     => 'stdClass',
    'arguments' => [],
));
