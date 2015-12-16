<?php

use Doctrine\ORM\Mapping\ClassMetadata;

$metadata->mapField(array(
   'id'                 => true,
   'fieldName'          => 'id',
   'columnDefinition'   => 'INT unsigned NOT NULL',
));

$metadata->mapField(array(
    'fieldName'         => 'value',
    'columnDefinition'  => 'VARCHAR(255) NOT NULL'
));

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
