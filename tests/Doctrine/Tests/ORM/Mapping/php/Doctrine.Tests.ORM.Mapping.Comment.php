<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->setInheritanceType(ClassMetadataInfo::INHERITANCE_TYPE_NONE);
$metadata->setPrimaryTable(array(
   'indexes' => array(
       array('columns' => array('content'), 'flags' => array('fulltext'), 'options'=> array('where' => 'content IS NOT NULL'))
   )
  ));

$metadata->mapField(array(
    'fieldName' => 'content',
    'type' => 'text',
    'scale' => 0,
    'length' => NULL,
    'unique' => false,
    'nullable' => false,
    'precision' => 0,
    'columnName' => 'content',
));