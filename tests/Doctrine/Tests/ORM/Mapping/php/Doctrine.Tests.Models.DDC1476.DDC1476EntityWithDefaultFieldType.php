<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->mapField(
    [
        'id'         => true,
        'fieldName'  => 'id',
    ]
);
$metadata->mapField(
    ['fieldName' => 'name']
);
$metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_NONE);
