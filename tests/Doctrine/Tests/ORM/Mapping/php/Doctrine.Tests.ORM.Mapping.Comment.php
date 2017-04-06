<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;

$tableMetadata = new Mapping\TableMetadata();

$tableMetadata->addIndex(
    [
        'name'    => null,
        'columns' => ['content'],
        'unique'  => false,
        'flags'   => ['fulltext'],
        'options' => ['where' => 'content IS NOT NULL'],
    ]
);

/* @var $metadata ClassMetadata */
$metadata->setTable($tableMetadata);
$metadata->setInheritanceType(Mapping\InheritanceType::NONE);

$fieldMetadata = new Mapping\FieldMetadata('content');

$fieldMetadata->setType(Type::getType('text'));
$fieldMetadata->setNullable(false);
$fieldMetadata->setUnique(false);

$metadata->addProperty($fieldMetadata);
