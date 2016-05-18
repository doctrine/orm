<?php

use Doctrine\DBAL\Types\Type;
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

$metadata->addProperty(
    'content',
    Type::getType('text'),
    [
        'length'   => NULL,
        'unique'   => false,
        'nullable' => false,
    ]
);
