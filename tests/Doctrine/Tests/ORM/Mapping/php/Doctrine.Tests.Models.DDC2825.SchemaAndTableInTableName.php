<?php

use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */

$metadata->setPrimaryTable(array(
    'name' => 'implicit_schema.implicit_table',
));

$metadata->mapField(array(
    'id'         => true,
    'fieldName'  => 'id',
));

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
