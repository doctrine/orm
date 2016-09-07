<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;
use Doctrine\DBAL\Types\Type;
use Doctrine\Tests\Models\Cache\Attraction;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\Travel;

/* @var $metadata ClassMetadata */
$metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_NONE);
$metadata->setPrimaryTable(['name' => 'cache_city']);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_IDENTITY);
$metadata->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_DEFERRED_IMPLICIT);
$metadata->enableCache(['usage' => ClassMetadata::CACHE_USAGE_READ_ONLY]);

$fieldMetadata = new Mapping\FieldMetadata('id');
$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setPrimaryKey(true);

$metadata->addProperty($fieldMetadata);

$fieldMetadata = new Mapping\FieldMetadata('name');
$fieldMetadata->setType(Type::getType('string'));

$metadata->addProperty($fieldMetadata);

$joinColumns = [];

$joinColumn = new Mapping\JoinColumnMetadata();

$joinColumn->setColumnName("state_id");
$joinColumn->setReferencedColumnName("id");

$joinColumns[] = $joinColumn;

$metadata->mapOneToOne(
    [
        'fieldName'    => 'state',
        'targetEntity' => State::class,
        'inversedBy'   => 'cities',
        'joinColumns'  => $joinColumns,
    ]
);

$metadata->enableAssociationCache('state', ['usage' => ClassMetadata::CACHE_USAGE_READ_ONLY]);

$metadata->mapManyToMany(
    [
       'fieldName'    => 'travels',
       'targetEntity' => Travel::class,
       'mappedBy'     => 'visitedCities',
    ]
);

$metadata->mapOneToMany(
    [
       'fieldName'    => 'attractions',
       'targetEntity' => Attraction::class,
       'mappedBy'     => 'city',
       'orderBy'      => ['name' => 'ASC'],
    ]
);

$metadata->enableAssociationCache('attractions', ['usage' => ClassMetadata::CACHE_USAGE_READ_ONLY]);
