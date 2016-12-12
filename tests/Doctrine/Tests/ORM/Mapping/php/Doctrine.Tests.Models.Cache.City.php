<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Tests\Models\Cache\Attraction;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\Travel;

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
   'targetEntity'   => State::class,
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
   'targetEntity' => Travel::class,
   'mappedBy' => 'visitedCities',
    ]
);

$metadata->mapOneToMany(
    [
   'fieldName' => 'attractions',
   'targetEntity' => Attraction::class,
   'mappedBy' => 'city',
   'orderBy' => ['name' => 'ASC',],
    ]
);
$metadata->enableAssociationCache('attractions', [
    'usage' => ClassMetadataInfo::CACHE_USAGE_READ_ONLY
]
);
