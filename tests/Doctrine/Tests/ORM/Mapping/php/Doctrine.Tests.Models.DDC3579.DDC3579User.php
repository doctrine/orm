<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->mapField(
    [
   'id'         => true,
   'fieldName'  => 'id',
   'type'       => 'integer',
   'columnName' => 'user_id',
   'length'     => 150,
    ]
);

$metadata->mapField(
    [
    'fieldName' => 'name',
    'type'      => 'string',
    'columnName'=> 'user_name',
    'nullable'  => true,
    'unique'    => false,
    'length'    => 250,
    ]
);

$metadata->mapManyToMany(
    [
   'fieldName'      => 'groups',
   'targetEntity'   => 'DDC3579Group'
    ]
);

$metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);
