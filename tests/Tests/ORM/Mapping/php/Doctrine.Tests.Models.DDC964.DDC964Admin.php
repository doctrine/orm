<?php

declare(strict_types=1);

$metadata->setAssociationOverride(
    'address',
    [
        'joinColumns' => [
            [
                'name' => 'adminaddress_id',
                'referencedColumnName' => 'id',
            ],
        ],
    ]
);

$metadata->setAssociationOverride(
    'groups',
    [
        'joinTable' => [
            'name'      => 'ddc964_users_admingroups',
            'joinColumns' => [
                ['name' => 'adminuser_id'],
            ],

            'inverseJoinColumns' => [
                ['name' => 'admingroup_id'],
            ],
        ],
    ]
);
