<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata = new ClassMetadataInfo('PhpTest');
$metadata->setInheritanceType(ClassMetadataInfo::INHERITANCE_TYPE_NONE);
$metadata->setPrimaryTable(array(
   'name' => 'php_test',
  ));
$metadata->setChangeTrackingPolicy(ClassMetadataInfo::CHANGETRACKING_DEFERRED_IMPLICIT);
$metadata->mapField(array(
   'id' => true,
   'fieldName' => 'id',
   'type' => 'integer',
   'columnName' => 'id',
  ));
$metadata->mapField(array(
   'fieldName' => 'name',
   'type' => 'string',
   'length' => 50,
   'columnName' => 'name',
  ));
$metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);