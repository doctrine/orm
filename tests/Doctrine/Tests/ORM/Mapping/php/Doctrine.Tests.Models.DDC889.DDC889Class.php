<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->mapField(array(
   'id'         => true,
   'fieldName'  => 'id',
   'type'       => 'integer',
   'columnName' => 'id',
));

//$metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);