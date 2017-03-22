<?php

use Doctrine\ORM\Mapping;

$metadata->setCache(
    new Mapping\CacheMetadata(
        Mapping\CacheUsage::READ_ONLY,
        'doctrine_tests_orm_mapping_phpslc'
    )
);

$association = new Mapping\ManyToOneAssociationMetadata('foo');

$association->setTargetEntity('PHPSLCFoo');
$association->setPrimaryKey(true);

$metadata->addProperty($association);
