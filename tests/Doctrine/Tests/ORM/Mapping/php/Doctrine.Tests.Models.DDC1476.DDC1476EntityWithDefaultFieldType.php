<?php

use Doctrine\ORM\Mapping\ClassMetadata;

$metadata->mapField(array(
   'id'         => true,
   'fieldName'  => 'id',
));
$metadata->mapField(array(
   'fieldName'  => 'name'
));
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
