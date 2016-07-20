<?php

$metadata->setAssociationOverride('address',array(
    'joinColumns'=>array(
        array(
            'name' => 'adminaddress_id',
            'referencedColumnName' => 'id',
            'onDelete' => null,
        )
    )
));

$metadata->setAssociationOverride('groups',array(
    'joinTable' => array (
        'name'      => 'ddc964_users_admingroups',
        'joinColumns' => array(
            array(
                'name' => 'adminuser_id',
                'onDelete' => null,
            )
        ),
        'inverseJoinColumns' => array(
            array (
                'name' => 'admingroup_id',
                'onDelete' => null,
            )
        )
  )
));