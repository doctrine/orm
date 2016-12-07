<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->setInheritanceType(ClassMetadataInfo::INHERITANCE_TYPE_NONE);
$metadata->setPrimaryTable(['name' => 'cache_city']);
$metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_IDENTITY);
$metadata->setChangeTrackingPolicy(ClassMetadataInfo::CHANGETRACKING_DEFERRED_IMPLICIT);

$metadata->enableCache(
    [
    'usage' => ClassMetadataInfo::CACHE_USAGE_READ_ONLY
    ]
);

$metadata->mapField(
    [
   'fieldName' => 'id',
   'type' => 'integer',
   'id' => true,
    ]
);

$metadata->mapField(
    [
   'fieldName' => 'name',
   'type' => 'string',
    ]
);


$metadata->mapOneToOne(
    [
   'fieldName'      => 'state',
   'targetEntity'   => 'Doctrine\\Tests\\Models\\Cache\\State',
   'inversedBy'     => 'cities',
   'joinColumns'    =>
   [
       [
    'name' => 'state_id',
    'referencedColumnName' => 'id',
       ]
   ]
    ]
);
$metadata->enableAssociationCache('state', [
    'usage' => ClassMetadataInfo::CACHE_USAGE_READ_ONLY
]
);

$metadata->mapManyToMany(
    [
   'fieldName' => 'travels',
   'targetEntity' => 'Doctrine\\Tests\\Models\\Cache\\Travel',
   'mappedBy' => 'visitedCities',
    ]
);

$metadata->mapOneToMany(
    [
   'fieldName' => 'attractions',
   'targetEntity' => 'Doctrine\\Tests\\Models\\Cache\\Attraction',
   'mappedBy' => 'city',
   'orderBy' => ['name' => 'ASC',],
    ]
);
$metadata->enableAssociationCache('attractions', [
    'usage' => ClassMetadataInfo::CACHE_USAGE_READ_ONLY
]
);
