<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */
$metadata->addProperty(
    'id',
    Type::getType('integer'),
    [
        'id'         => true,
        'columnName' => 'user_id',
    ]
);

$metadata->addProperty(
    'name',
    Type::getType('string'),
    [
        'columnName' => 'user_name',
        'nullable'   => true,
        'unique'     => false,
        'length'     => 250,
    ]
);

$metadata->mapManyToMany(array(
    'fieldName'      => 'groups',
    'targetEntity'   => 'DDC3579Group'
));

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
