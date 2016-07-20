<?php

$metadata->setAssociationOverride(
    'address',
    [
        'joinColumns'=> [
            [
                'name' => 'adminaddress_id',
                'referencedColumnName' => 'id',
                'onDelete' => null,
            ]
        ]
    ]
);

$metadata->setAssociationOverride(
    'groups',
    [
        'joinTable' => [
            'name'      => 'ddc964_users_admingroups',
            'joinColumns' => [
                [
                    'name' => 'adminuser_id',
                    'onDelete' => null,
                ]
            ],
            'inverseJoinColumns' => [
                [
                    'name' => 'admingroup_id',
                    'onDelete' => null,
                ]
            ]
        ]
    ]
);
