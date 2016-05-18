<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$metadata->addProperty('creditCardNumber', Type::getType('string'));