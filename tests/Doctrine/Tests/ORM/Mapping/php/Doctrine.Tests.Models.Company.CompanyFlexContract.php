<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */
$metadata->addProperty('hoursWorked', Type::getType('integer'), ['columnName' => 'hoursWorked']);
$metadata->addProperty('pricePerHour', Type::getType('integer'), ['columnName' => 'pricePerHour']);
