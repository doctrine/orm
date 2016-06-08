<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */
$metadata->addProperty('fixPrice', Type::getType('integer'), ['columnName' => 'fixPrice']);
