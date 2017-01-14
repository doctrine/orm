<?php

use Doctrine\ORM\Mapping;

$metadata->setCache(
    new Mapping\CacheMetadata(
        Mapping\CacheUsage::READ_ONLY,
        'doctrine_tests_orm_mapping_phpslc'
    )
);

$metadata->mapManyToOne(
    [
        'fieldName'    => 'foo',
        'id'           => true,
        'targetEntity' => 'PHPSLCFoo',
    ]
);
