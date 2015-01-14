<?php

use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */

$metadata->setPrimaryTable(array(
    'name'   => 'mytable',
    'schema' => 'myschema',
));

$metadata->mapField(array(
    'id'         => true,
    'fieldName'  => 'id',
));

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
