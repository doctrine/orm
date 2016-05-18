<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_NONE);

$metadata->setPrimaryTable(array(
   'indexes' => array(
       array(
           'columns' => array('content'),
           'flags'   => array('fulltext'),
           'options' => array('where' => 'content IS NOT NULL'),
       ),
   )
));

$metadata->addProperty('content', Type::getType('text'), array(
    'length'   => NULL,
    'unique'   => false,
    'nullable' => false,
));