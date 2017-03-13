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
$metadata->setCache(
    new Mapping\CacheMetadata(
        Mapping\CacheUsage::READ_ONLY,
        'doctrine_tests_models_cache_city'
    )
);

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

$association = new Mapping\OneToOneAssociationMetadata('state');

$association->setJoinColumns($joinColumns);
$association->setTargetEntity(State::class);
$association->setInversedBy('cities');

$metadata->mapOneToOne($association);

$metadata->enableAssociationCache('state', ['usage' => Mapping\CacheUsage::READ_ONLY]);

$association = new Mapping\ManyToManyAssociationMetadata('travels');

$association->setTargetEntity(Travel::class);
$association->setMappedBy('visitedCities');

$metadata->mapManyToMany($association);

$association = new Mapping\OneToManyAssociationMetadata('attractions');

$association->setTargetEntity(Attraction::class);
$association->setMappedBy('city');
$association->setOrderBy(['name' => 'ASC']);

$metadata->mapOneToMany($association);

$metadata->enableAssociationCache('attractions', ['usage' => Mapping\CacheUsage::READ_ONLY]);
