<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/* @var $metadata ClassMetadataInfo */
$metadata->setInheritanceType(ClassMetadataInfo::INHERITANCE_TYPE_SINGLE_TABLE);
$metadata->setDiscriminatorColumn([
   'name' => 'dtype',
   'type' => 'string',
   'length' => 255,
   'fieldName' => 'dtype',
  ]);
$metadata->setDiscriminatorMap([
   'cat' => 'Doctrine\\Tests\\ORM\\Mapping\\Cat',
   'dog' => 'Doctrine\\Tests\\ORM\\Mapping\\Dog',
  ]);
$metadata->setChangeTrackingPolicy(ClassMetadataInfo::CHANGETRACKING_DEFERRED_IMPLICIT);
$metadata->mapField([
   'fieldName' => 'id',
   'type' => 'string',
   'length' => NULL,
   'precision' => 0,
   'scale' => 0,
   'nullable' => false,
   'unique' => false,
   'id' => true,
   'columnName' => 'id',
  ]);
$metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_CUSTOM);
$metadata->setCustomGeneratorDefinition(["class" => "stdClass"]);
