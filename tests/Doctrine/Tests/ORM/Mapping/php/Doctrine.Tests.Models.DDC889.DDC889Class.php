<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;

/** @var ClassMetadata $metadata */
$metadata->addProperty('id', Type::getType('integer'), ['id' => true]);
