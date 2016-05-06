<?php

use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata $metadata */
$metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE);
$metadata->setDiscriminatorColumn(array(
   'name' => 'dtype',
   'type' => 'string',
   'length' => 255,
   'fieldName' => 'dtype',
  ));
$metadata->setDiscriminatorMap(array(
   'cat' => 'Doctrine\\Tests\\ORM\\Mapping\\Cat',
   'dog' => 'Doctrine\\Tests\\ORM\\Mapping\\Dog',
  ));
$metadata->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_DEFERRED_IMPLICIT);
$metadata->mapField(array(
   'fieldName' => 'id',
   'type' => 'string',
   'length' => NULL,
   'precision' => 0,
   'scale' => 0,
   'nullable' => false,
   'unique' => false,
   'id' => true,
   'columnName' => 'id',
  ));
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_CUSTOM);
$metadata->setCustomGeneratorDefinition(array("class" => "stdClass"));
