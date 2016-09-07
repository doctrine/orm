<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */
$metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_NONE);

$metadata->setPrimaryTable(
    [
       'indexes' => [
           [
               'columns' => ['content'],
               'flags'   => ['fulltext'],
               'options' => ['where' => 'content IS NOT NULL'],
           ],
       ]
    ]
);

$fieldMetadata = new Mapping\FieldMetadata('content');

$fieldMetadata->setType(Type::getType('text'));
$fieldMetadata->setNullable(false);
$fieldMetadata->setUnique(false);

$metadata->addProperty($fieldMetadata);
