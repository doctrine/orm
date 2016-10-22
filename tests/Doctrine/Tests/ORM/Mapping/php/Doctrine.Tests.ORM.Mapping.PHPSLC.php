<?php

use Doctrine\ORM\Mapping;

$metadata->enableCache(array(
    'usage' => Mapping\CacheUsage::READ_ONLY,
));

$metadata->mapManyToOne(array(
    'fieldName'    => 'foo',
    'id'           => true,
    'targetEntity' => 'PHPSLCFoo',
));