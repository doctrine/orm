<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Tests\Models\DDC889\DDC889SuperClass;

$metadata->mapField(
    [
   'fieldName'  => 'name',
   'type'       => 'string',
    ]
);
$metadata->isMappedSuperclass = true;
$metadata->setCustomRepositoryClass(DDC889SuperClass::class);
$metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);
