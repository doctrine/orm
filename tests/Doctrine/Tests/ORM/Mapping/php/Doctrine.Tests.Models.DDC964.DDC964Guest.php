<?php
$metadata->setAttributeOverride('id', array(
    'columnName'    => 'guest_id',
    'type'          => 'integer',
    'length'        => 140,
));

$metadata->setAttributeOverride('name',array(
    'columnName'    => 'guest_name',
    'nullable'      => false,
    'unique'        => true,
    'length'        => 240,
));