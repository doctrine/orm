<?php

use Doctrine\ORM\Mapping;

$joinColumns = array();

$joinColumn = new Mapping\JoinColumnMetadata();

$joinColumn->setColumnName('adminaddress_id');
$joinColumn->setReferencedColumnName('id');
$joinColumn->setOnDelete(null);

$joinColumns[] = $joinColumn;

$metadata->setAssociationOverride('address', array(
    'joinColumns' => $joinColumns,
));

$joinColumns = array();

$joinColumn = new Mapping\JoinColumnMetadata();

$joinColumn->setColumnName('adminuser_id');

$joinColumns[] = $joinColumn;

$inverseJoinColumns = array();

$joinColumn = new Mapping\JoinColumnMetadata();

$joinColumn->setColumnName('admingroup_id');

$inverseJoinColumns[] = $joinColumn;

$joinTable = array(
    'name'               => 'ddc964_users_admingroups',
    'joinColumns'        => $joinColumns,
    'inverseJoinColumns' => $inverseJoinColumns,
);

$metadata->setAssociationOverride('groups', array(
    'joinTable' => $joinTable,
));