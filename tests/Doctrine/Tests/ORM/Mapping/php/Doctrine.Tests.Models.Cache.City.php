<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->setInheritanceType(ClassMetadataInfo::INHERITANCE_TYPE_NONE);
$metadata->setPrimaryTable(array('name' => 'cache_city'));
$metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_IDENTITY);
$metadata->setChangeTrackingPolicy(ClassMetadataInfo::CHANGETRACKING_DEFERRED_IMPLICIT);

$metadata->enableCache(array(
    'usage' => ClassMetadataInfo::CACHE_USAGE_READ_ONLY
));

$metadata->mapField(array(
   'fieldName' => 'id',
   'type' => 'integer',
   'id' => true,
  ));

$metadata->mapField(array(
   'fieldName' => 'name',
   'type' => 'string',
));


$metadata->mapOneToOne(array(
   'fieldName'      => 'state',
   'targetEntity'   => 'Doctrine\\Tests\\Models\\Cache\\State',
   'inversedBy'     => 'cities',
   'joinColumns'    =>
   array(array(
    'name' => 'state_id',
    'referencedColumnName' => 'id',
   ))
));
$metadata->enableAssociationCache('state', array(
    'usage' => ClassMetadataInfo::CACHE_USAGE_READ_ONLY
));

$metadata->mapManyToMany(array(
   'fieldName' => 'travels',
   'targetEntity' => 'Doctrine\\Tests\\Models\\Cache\\Travel',
   'mappedBy' => 'visitedCities',
));

$metadata->mapOneToMany(array(
   'fieldName' => 'attractions',
   'targetEntity' => 'Doctrine\\Tests\\Models\\Cache\\Attraction',
   'mappedBy' => 'city',
   'orderBy' => array('name' => 'ASC',),
));
$metadata->enableAssociationCache('attractions', array(
    'usage' => ClassMetadataInfo::CACHE_USAGE_READ_ONLY
));