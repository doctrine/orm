<?php
$metadata->setAttributeOverride('id', [
    'columnName'    => 'guest_id',
    'type'          => 'integer',
    'length'        => 140,
]
);

$metadata->setAttributeOverride('name',
    [
    'columnName'    => 'guest_name',
    'nullable'      => false,
    'unique'        => true,
    'length'        => 240,
    ]
);
