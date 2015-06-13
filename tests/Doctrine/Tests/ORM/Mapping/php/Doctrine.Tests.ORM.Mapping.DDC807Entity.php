<?php

use Doctrine\ORM\Mapping\ClassMetadata;

$metadata->mapField(array(
   'id'                 => true,
   'fieldName'          => 'id',
));

$metadata->setDiscriminatorColumn(array(
    'name'              => "dtype",
    'columnDefinition'  => "ENUM('ONE','TWO')"
));

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);