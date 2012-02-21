<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->mapField(array(
   'id'                 => true,
   'fieldName'          => 'id',
));

$metadata->setDiscriminatorColumn(array(
    'name'              => "dtype",
    'columnDefinition'  => "ENUM('ONE','TWO')"
));

$metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_NONE);