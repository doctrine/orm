<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\ORM\Mapping\Address;
use Doctrine\Tests\ORM\Mapping\Group;
use Doctrine\Tests\ORM\Mapping\Phonenumber;

/* @var $metadata ClassMetadata */
$tableMetadata = new Mapping\TableMetadata();

$tableMetadata->setName('cms_users');
$tableMetadata->addIndex(
    [
        'name'    => 'name_idx',
        'columns' => ['name'],
        'unique'  => false,
        'options' => [],
        'flags'   => [],
    ]
);
$tableMetadata->addIndex(
    [
        'name'    => null,
        'columns' => ['user_email'],
        'unique'  => false,
        'options' => [],
        'flags'   => [],
    ]
);
$tableMetadata->addUniqueConstraint(
    [
        'name'    => 'search_idx',
        'columns' => ['name', 'user_email'],
        'options' => [],
        'flags'   => [],
    ]
);
$tableMetadata->addOption('foo', 'bar');
$tableMetadata->addOption('baz', ['key' => 'val']);

$metadata->setPrimaryTable($tableMetadata);
$metadata->setInheritanceType(Mapping\InheritanceType::NONE);
$metadata->setChangeTrackingPolicy(Mapping\ChangeTrackingPolicy::DEFERRED_IMPLICIT);

$metadata->addLifecycleCallback('doStuffOnPrePersist', 'prePersist');
$metadata->addLifecycleCallback('doOtherStuffOnPrePersistToo', 'prePersist');
$metadata->addLifecycleCallback('doStuffOnPostPersist', 'postPersist');

$metadata->addNamedQuery(
    [
        'name'  => 'all',
        'query' => 'SELECT u FROM __CLASS__ u'
    ]
);

$metadata->setGeneratorDefinition(
    [
        'sequenceName'   => 'tablename_seq',
        'allocationSize' => 100,
    ]
);

$fieldMetadata = new Mapping\FieldMetadata('id');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setPrimaryKey(true);
$fieldMetadata->setOptions(['foo' => 'bar', 'unsigned' => false]);

$metadata->addProperty($fieldMetadata);

$fieldMetadata = new Mapping\FieldMetadata('name');

$fieldMetadata->setType(Type::getType('string'));
$fieldMetadata->setLength(50);
$fieldMetadata->setColumnName('name');
$fieldMetadata->setNullable(true);
$fieldMetadata->setUnique(true);
$fieldMetadata->setOptions(
    [
        'foo' => 'bar',
        'baz' => ['key' => 'val'],
        'fixed' => false,
    ]
);

$metadata->addProperty($fieldMetadata);

$fieldMetadata = new Mapping\FieldMetadata('email');

$fieldMetadata->setType(Type::getType('string'));
$fieldMetadata->setColumnName('user_email');
$fieldMetadata->setColumnDefinition('CHAR(32) NOT NULL');

$metadata->addProperty($fieldMetadata);

$versionFieldMetadata = new Mapping\VersionFieldMetadata('version');

$versionFieldMetadata->setType(Type::getType('integer'));

$metadata->addProperty($versionFieldMetadata);
$metadata->setVersionProperty($versionFieldMetadata);

$metadata->setIdGeneratorType(Mapping\GeneratorType::AUTO);

$joinColumns = [];

$joinColumn = new Mapping\JoinColumnMetadata();
$joinColumn->setColumnName('address_id');
$joinColumn->setReferencedColumnName('id');
$joinColumn->setOnDelete('CASCADE');

$joinColumns[] = $joinColumn;

$association = new Mapping\OneToOneAssociationMetadata('address');

$association->setJoinColumns($joinColumns);
$association->setTargetEntity(Address::class);
$association->setInversedBy('user');
$association->setCascade(['remove']);
$association->setOrphanRemoval(false);

$metadata->mapOneToOne($association);

$association = new Mapping\OneToManyAssociationMetadata('phonenumbers');

$association->setTargetEntity(Phonenumber::class);
$association->setMappedBy('user');
$association->setCascade(['persist']);
$association->setOrphanRemoval(true);
$association->setOrderBy(['number' => 'ASC']);

$metadata->mapOneToMany($association);

$joinTable = new Mapping\JoinTableMetadata();
$joinTable->setName('cms_users_groups');

$joinColumn = new Mapping\JoinColumnMetadata();
$joinColumn->setColumnName("user_id");
$joinColumn->setReferencedColumnName("id");
$joinColumn->setNullable(false);
$joinColumn->setUnique(false);

$joinTable->addJoinColumn($joinColumn);

$joinColumn = new Mapping\JoinColumnMetadata();

$joinColumn->setColumnName("group_id");
$joinColumn->setReferencedColumnName("id");
$joinColumn->setColumnDefinition("INT NULL");

$joinTable->addInverseJoinColumn($joinColumn);

$association = new Mapping\ManyToManyAssociationMetadata('groups');

$association->setJoinTable($joinTable);
$association->setTargetEntity(Group::class);
$association->setCascade(['remove', 'persist', 'refresh', 'merge', 'detach']);

$metadata->mapManyToMany($association);
