<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->mapField(
    [
   'id'                 => true,
   'fieldName'          => 'id',
   'columnDefinition'   => 'INT unsigned NOT NULL',
    ]
);

$metadata->mapField(
    [
    'fieldName'         => 'value',
    'columnDefinition'  => 'VARCHAR(255) NOT NULL'
    ]
);

$metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_NONE);
