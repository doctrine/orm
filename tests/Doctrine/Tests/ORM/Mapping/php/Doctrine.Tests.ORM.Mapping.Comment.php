<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_NONE);

$metadata->setPrimaryTable(array(
   'indexes' => array(
       array(
           'unique'  => false,
           'columns' => array('content'),
           'flags'   => array('fulltext'),
           'options' => array('where' => 'content IS NOT NULL'),
       ),
   )
));

$fieldMetadata = new Mapping\FieldMetadata('content');

$fieldMetadata->setType(Type::getType('text'));
$fieldMetadata->setNullable(false);
$fieldMetadata->setUnique(false);

$metadata->addProperty($fieldMetadata);