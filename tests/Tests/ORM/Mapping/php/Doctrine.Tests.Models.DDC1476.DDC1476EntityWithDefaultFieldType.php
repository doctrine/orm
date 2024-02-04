<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

$metadata->mapField(
    [
        'id'         => true,
        'fieldName'  => 'id',
    ]
);
$metadata->mapField(
    ['fieldName' => 'name']
);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
