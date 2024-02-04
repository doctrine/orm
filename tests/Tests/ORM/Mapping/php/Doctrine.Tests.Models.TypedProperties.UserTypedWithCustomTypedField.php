<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

$metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_NONE);
$metadata->setPrimaryTable(
    ['name' => 'cms_users_typed_with_custom_typed_field']
);

$metadata->mapField(
    [
        'id' => true,
        'fieldName' => 'id',
    ]
);

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

$metadata->mapField(
    ['fieldName' => 'customId']
);

$metadata->mapField(
    ['fieldName' => 'customIntTypedField']
);
