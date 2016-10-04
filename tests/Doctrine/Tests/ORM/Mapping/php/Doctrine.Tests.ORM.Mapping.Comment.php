<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$tableMetadata = new Mapping\TableMetadata();

$tableMetadata->addIndex(array(
    'name'    => null,
    'columns' => array('content'),
    'unique'  => false,
    'flags'   => array('fulltext'),
    'options' => array('where' => 'content IS NOT NULL'),
));

$metadata->setPrimaryTable($tableMetadata);
$metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_NONE);

$fieldMetadata = new Mapping\FieldMetadata('content');

$fieldMetadata->setType(Type::getType('text'));
$fieldMetadata->setNullable(false);
$fieldMetadata->setUnique(false);

$metadata->addProperty($fieldMetadata);