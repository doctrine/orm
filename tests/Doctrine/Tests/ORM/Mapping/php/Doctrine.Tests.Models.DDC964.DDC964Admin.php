<?php

use Doctrine\ORM\Mapping;

$joinColumns = [];

$joinColumn = new Mapping\JoinColumnMetadata();

$joinColumn->setColumnName('adminaddress_id');
$joinColumn->setReferencedColumnName('id');
$joinColumn->setOnDelete('');

$joinColumns[] = $joinColumn;

$metadata->setAssociationOverride('address', ['joinColumns' => $joinColumns]);

$joinColumns = [];

$joinColumn = new Mapping\JoinColumnMetadata();
$joinColumn->setColumnName('adminuser_id');

$joinColumns[] = $joinColumn;

$inverseJoinColumns = [];

$joinColumn = new Mapping\JoinColumnMetadata();
$joinColumn->setColumnName('admingroup_id');

$inverseJoinColumns[] = $joinColumn;

$joinTable = [
    'name'               => 'ddc964_users_admingroups',
    'joinColumns'        => $joinColumns,
    'inverseJoinColumns' => $inverseJoinColumns,
];

$metadata->setAssociationOverride('groups', ['joinTable' => $joinTable]);
