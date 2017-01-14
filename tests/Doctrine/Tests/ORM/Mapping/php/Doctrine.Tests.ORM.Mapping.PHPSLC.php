<?php

use Doctrine\ORM\Mapping;

$metadata->setCache(new Mapping\CacheMetadata(Mapping\CacheUsage::READ_ONLY));

$metadata->mapManyToOne(
    [
        'fieldName'    => 'foo',
        'id'           => true,
        'targetEntity' => 'PHPSLCFoo',
    ]
);
