<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->mapField([
   'fieldName'  => 'name',
   'type'       => 'string',
  ]);
$metadata->isMappedSuperclass = true;
$metadata->setCustomRepositoryClass("Doctrine\Tests\Models\DDC889\DDC889SuperClass");
$metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);