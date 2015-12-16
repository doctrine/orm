<?php

use Doctrine\ORM\Mapping\ClassMetadata;

$metadata->mapField(array(
   'fieldName'  => 'name',
   'type'       => 'string',
  ));
$metadata->isMappedSuperclass = true;
$metadata->setCustomRepositoryClass("Doctrine\Tests\Models\DDC889\DDC889SuperClass");
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
