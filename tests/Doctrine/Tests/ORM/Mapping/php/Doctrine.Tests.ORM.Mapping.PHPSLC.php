<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->enableCache(
    [
    'usage' => ClassMetadataInfo::CACHE_USAGE_READ_ONLY
    ]
);
$metadata->mapManyToOne(
    [
    'fieldName'      => 'foo',
    'id'         => true,
    'targetEntity'   => 'PHPSLCFoo'
    ]
);
