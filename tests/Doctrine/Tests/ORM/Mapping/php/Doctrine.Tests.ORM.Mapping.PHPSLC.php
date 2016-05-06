<?php

use Doctrine\ORM\Mapping\ClassMetadata;

$metadata->enableCache(array(
    'usage' => ClassMetadata::CACHE_USAGE_READ_ONLY
));
$metadata->mapManyToOne(array(
    'fieldName'      => 'foo',
    'id'         => true,
    'targetEntity'   => 'PHPSLCFoo'
));