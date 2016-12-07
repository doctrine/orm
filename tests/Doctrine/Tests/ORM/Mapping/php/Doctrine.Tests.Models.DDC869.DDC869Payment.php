<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->mapField(
    [
   'id'         => true,
   'fieldName'  => 'id',
   'type'       => 'integer',
   'columnName' => 'id',
    ]
);
$metadata->mapField(
    [
   'fieldName'  => 'value',
   'type'       => 'float',
    ]
);
$metadata->isMappedSuperclass = true;
$metadata->setCustomRepositoryClass("Doctrine\Tests\Models\DDC869\DDC869PaymentRepository");
$metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);
