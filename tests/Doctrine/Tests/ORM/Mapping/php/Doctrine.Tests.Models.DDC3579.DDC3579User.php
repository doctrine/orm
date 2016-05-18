<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$metadata->addProperty('id', Type::getType('integer'), array(
    'id'         => true,
    'columnName' => 'user_id',
));

$metadata->addProperty('name', Type::getType('string'), array(
    'columnName' => 'user_name',
    'nullable'   => true,
    'unique'     => false,
    'length'     => 250,
));

$metadata->mapManyToMany(array(
    'fieldName'      => 'groups',
    'targetEntity'   => 'DDC3579Group'
));

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
