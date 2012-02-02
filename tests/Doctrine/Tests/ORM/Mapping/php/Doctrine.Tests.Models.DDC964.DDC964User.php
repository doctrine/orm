<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->mapField(array(
   'id'         => true,
   'fieldName'  => 'id',
   'type'       => 'integer',
   'columnName' => 'id',
));
$metadata->mapField(array(
   'fieldName'  => 'name',
   'type'       => 'string',
));

$metadata->mapManyToOne(array(
   'fieldName'      => 'address',
   'targetEntity'   => 'DDC964Address',
   'cascade'        => array('persist','merge'),
   'joinColumn'     => array('name'=>'address_id', 'referencedColumnMame'=>'id'),
));

$metadata->mapManyToMany(array(
   'fieldName'      => 'groups',
   'targetEntity'   => 'DDC964Group',
   'inversedBy'     => 'users',
   'cascade'        => array('persist','merge','detach'),
   'joinTable'      => array(
        'name'          => 'ddc964_users_groups',
        'joinColumns'   => array(array(
            'name'=>'user_id',
            'referencedColumnName'=>'id',
        )),
        'inverseJoinColumns'=>array(array(
            'name'=>'group_id',
            'referencedColumnName'=>'id',
        ))
   )
));

$metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);