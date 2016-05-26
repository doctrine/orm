<?php

$metadata->setAttributeOverride('id', ['columnName' => 'guest_id']);

$metadata->setAttributeOverride('name',
    [
        'columnName' => 'guest_name',
        'nullable'   => false,
        'unique'     => true,
        'length'     => 240,
    ]
);
