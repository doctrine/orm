<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;

/** @var ClassMetadata $metadata */
$metadata->addProperty('id', Type::getType('integer'), array(
   'id' => true,
));