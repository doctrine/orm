<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->mapField(array(
   'id'                 => true,
   'fieldName'          => 'id',
   'columnDefinition'   => 'INT unsigned NOT NULL',
));

$metadata->mapField(array(
    'fieldName'         => 'value',
    'columnDefinition'  => 'VARCHAR(255) NOT NULL'
));

$metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_NONE);