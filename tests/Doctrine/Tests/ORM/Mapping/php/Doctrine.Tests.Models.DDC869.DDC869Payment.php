<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$metadata->isMappedSuperclass = true;

$metadata->addProperty('id', Type::getType('integer'), array(
    'id' => true,
));

$metadata->addProperty('value', Type::getType('float'));

$metadata->setCustomRepositoryClass("Doctrine\Tests\Models\DDC869\DDC869PaymentRepository");
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);