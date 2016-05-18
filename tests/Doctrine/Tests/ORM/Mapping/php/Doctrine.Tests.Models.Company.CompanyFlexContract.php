<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$metadata->addProperty('hoursWorked', Type::getType('integer'), array(
    'columnName' => 'hoursWorked',
));

$metadata->addProperty('pricePerHour', Type::getType('integer'), array(
    'columnName' => 'pricePerHour',
));
