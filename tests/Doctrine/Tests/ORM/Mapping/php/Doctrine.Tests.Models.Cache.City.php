<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$tableMetadata = new Mapping\TableMetadata();

$tableMetadata->setName('cache_city');

$metadata->setPrimaryTable($tableMetadata);
$metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_NONE);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_IDENTITY);
$metadata->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_DEFERRED_IMPLICIT);

$metadata->enableCache(array(
    'usage' => Mapping\CacheUsage::READ_ONLY,
));

$fieldMetadata = new Mapping\FieldMetadata('id');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setPrimaryKey(true);

$metadata->addProperty($fieldMetadata);

$fieldMetadata = new Mapping\FieldMetadata('name');

$fieldMetadata->setType(Type::getType('string'));

$metadata->addProperty($fieldMetadata);

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
    'usage' => Mapping\CacheUsage::READ_ONLY,
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
    'usage' => Mapping\CacheUsage::READ_ONLY,
));