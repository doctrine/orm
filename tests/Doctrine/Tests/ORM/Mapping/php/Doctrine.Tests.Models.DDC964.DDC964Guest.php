<?php

$metadata->setAttributeOverride('id', array(
    'columnName' => 'guest_id',
));

$metadata->setAttributeOverride('name',array(
    'columnName' => 'guest_name',
    'nullable'   => false,
    'unique'     => true,
    'length'     => 240,
));