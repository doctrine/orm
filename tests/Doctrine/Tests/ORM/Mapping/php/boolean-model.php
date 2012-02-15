<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->mapField(array(
   'id'         => true,
   'fieldName'  => 'id',
));
$metadata->mapField(array(
   'fieldName'  => 'booleanField',
   'type'       => 'boolean'
));
$metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_NONE);