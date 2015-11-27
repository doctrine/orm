<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->enableCache(array(
    'usage' => ClassMetadataInfo::CACHE_USAGE_READ_ONLY
));
$metadata->mapManyToOne(array(
    'fieldName'      => 'foo',
    'id'         => true,
    'targetEntity'   => 'PHPSLCFoo'
));