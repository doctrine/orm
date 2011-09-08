<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->mapField(array(
   'id'         => true,
   'fieldName'  => 'id',
   'type'       => 'integer',
   'columnName' => 'id',
));
$metadata->mapField(array(
   'fieldName'  => 'value',
   'type'       => 'float',
  ));
$metadata->isMappedSuperclass = true;
$metadata->setCustomRepositoryClass("Doctrine\Tests\Models\DDC869\DDC869PaymentRepository");
$metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);