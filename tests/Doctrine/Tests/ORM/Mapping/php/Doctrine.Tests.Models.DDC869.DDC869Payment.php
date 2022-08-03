<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Tests\Models\DDC869\DDC869PaymentRepository;

$metadata->mapField(
    [
        'id'         => true,
        'fieldName'  => 'id',
        'type'       => 'integer',
        'columnName' => 'id',
    ]
);
$metadata->mapField(
    [
        'fieldName'  => 'value',
        'type'       => 'float',
    ]
);
$metadata->isMappedSuperclass = true;
$metadata->setCustomRepositoryClass(DDC869PaymentRepository::class);
$metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);
