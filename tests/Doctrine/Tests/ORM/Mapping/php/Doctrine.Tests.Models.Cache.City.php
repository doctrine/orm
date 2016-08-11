<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_NONE);
$metadata->setPrimaryTable(array('name' => 'cache_city'));
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_IDENTITY);
$metadata->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_DEFERRED_IMPLICIT);

$metadata->enableCache(array(
    'usage' => ClassMetadata::CACHE_USAGE_READ_ONLY
));

$metadata->addProperty('id', Type::getType('integer'), array(
    'id' => true,
));

$metadata->addProperty('name', Type::getType('string'));

$joinColumns = array();

$joinColumn = new Mapping\JoinColumnMetadata();

$joinColumn->setColumnName("state_id");
$joinColumn->setReferencedColumnName("id");

$joinColumns[] = $joinColumn;

$metadata->mapOneToOne(array(
    'fieldName'      => 'state',
    'targetEntity'   => 'Doctrine\\Tests\\Models\\Cache\\State',
    'inversedBy'     => 'cities',
    'joinColumns'    => $joinColumns,
));

$metadata->enableAssociationCache('state', array(
    'usage' => ClassMetadata::CACHE_USAGE_READ_ONLY
));

$metadata->mapManyToMany(array(
   'fieldName'    => 'travels',
   'targetEntity' => 'Doctrine\\Tests\\Models\\Cache\\Travel',
   'mappedBy'     => 'visitedCities',
));

$metadata->mapOneToMany(array(
   'fieldName'    => 'attractions',
   'targetEntity' => 'Doctrine\\Tests\\Models\\Cache\\Attraction',
   'mappedBy'     => 'city',
   'orderBy'      => array(
       'name' => 'ASC'
   ),
));

$metadata->enableAssociationCache('attractions', array(
    'usage' => ClassMetadata::CACHE_USAGE_READ_ONLY
));