<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/** @var ClassMetadataInfo $metadata */

$metadata->mapField(array(
    'id'        => true,
    'fieldName' => 'dateField',
    'type'      => 'date'
));

$metadata->mapField(array(
    'id'        => true,
    'fieldName' => 'stringField',
    'type'      => 'string'
));

$metadata->mapManyToOne(array(
    'id'           => true,
    'fieldName'    => 'associationField',
    'targetEntity' => 'Field2',
    'joinColumns'  => array()
));

$metadata->identifier = array('dateField', 'associationField', 'stringField');
