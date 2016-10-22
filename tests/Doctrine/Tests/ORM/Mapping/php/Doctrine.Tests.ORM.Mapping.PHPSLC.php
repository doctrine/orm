<?php

use Doctrine\ORM\Mapping;

$metadata->enableCache(['usage' => Mapping\CacheUsage::READ_ONLY]);

$metadata->mapManyToOne(
    [
        'fieldName'    => 'foo',
        'id'           => true,
        'targetEntity' => 'PHPSLCFoo',
    ]
);
