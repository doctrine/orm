<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$metadata->addProperty('id', Type::getType('integer'), array(
   'id'         => true,
   'columnName' => 'user_id',
));

$metadata->addProperty('name', Type::getType('string'), array(
    'columnName'=> 'user_name',
    'nullable'  => true,
    'unique'    => false,
    'length'    => 250,
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

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);