<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;
use Doctrine\DBAL\Types\Type;
use Doctrine\Tests\Models\Cache\Attraction;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\Travel;

/* @var $metadata ClassMetadata */
$tableMetadata = new Mapping\TableMetadata();
$tableMetadata->setName('cache_city');

$metadata->setPrimaryTable($tableMetadata);
$metadata->setInheritanceType(Mapping\InheritanceType::NONE);
$metadata->setChangeTrackingPolicy(Mapping\ChangeTrackingPolicy::DEFERRED_IMPLICIT);
$metadata->setIdGeneratorType(Mapping\GeneratorType::IDENTITY);
$metadata->enableCache(['usage' => Mapping\CacheUsage::READ_ONLY]);

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

$metadata->enableAssociationCache('state', ['usage' => Mapping\CacheUsage::READ_ONLY]);

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

$metadata->enableAssociationCache('attractions', ['usage' => Mapping\CacheUsage::READ_ONLY]);
