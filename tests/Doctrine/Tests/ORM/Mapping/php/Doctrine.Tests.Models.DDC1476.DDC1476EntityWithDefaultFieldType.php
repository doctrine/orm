<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->mapField(array(
   'id'         => true,
   'fieldName'  => 'id',
));
$metadata->mapField(array(
   'fieldName'  => 'name'
));
$metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_NONE);