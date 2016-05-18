<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;

/* @var ClassMetadata $metadata */
$metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE);

$metadata->setDiscriminatorColumn(array(
    'name'      => 'dtype',
    'type'      => 'string',
    'length'    => 255,
    'fieldName' => 'dtype',
    'tableName' => $metadata->getTableName(),
));

$metadata->setDiscriminatorMap(array(
    'cat' => 'Doctrine\\Tests\\ORM\\Mapping\\Cat',
    'dog' => 'Doctrine\\Tests\\ORM\\Mapping\\Dog',
));

$metadata->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_DEFERRED_IMPLICIT);

$metadata->addProperty('id', Type::getType('string'), array(
    'length'   => NULL,
    'nullable' => false,
    'unique'   => false,
    'id'       => true,
));

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_CUSTOM);

$metadata->setCustomGeneratorDefinition(array("class" => "stdClass"));
