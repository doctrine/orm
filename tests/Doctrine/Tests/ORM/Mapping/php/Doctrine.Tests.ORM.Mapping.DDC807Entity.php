<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->mapField(
    [
   'id'                 => true,
   'fieldName'          => 'id',
    ]
);

$metadata->setDiscriminatorColumn(
    [
    'name'              => "dtype",
    'columnDefinition'  => "ENUM('ONE','TWO')"
    ]
);

$metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_NONE);
