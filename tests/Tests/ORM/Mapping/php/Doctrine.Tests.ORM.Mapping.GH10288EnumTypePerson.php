<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Models\GH10288\GH10288People;

$metadata->mapField(
    [
        'id'                 => true,
        'fieldName'          => 'id',
    ],
);

$metadata->setDiscriminatorColumn(
    [
        'name'     => 'discr',
        'enumType' => GH10288People::class,
    ],
);

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
