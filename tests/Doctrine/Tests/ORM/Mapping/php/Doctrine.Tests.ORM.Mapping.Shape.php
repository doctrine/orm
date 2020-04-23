<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Tests\ORM\Mapping\cube;

/* @var $metadata ClassMetadataInfo */
$metadata->setInheritanceType(ClassMetadataInfo::INHERITANCE_TYPE_SINGLE_TABLE);
$metadata->setDiscriminatorColumn([
   'name' => 'discr',
   'type' => 'string',
   'length' => 32,
]);
$metadata->setDiscriminatorMap([
   'cube' => cube::class,
]);
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
$metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);
