<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CustomType;

use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 * Holds fields whose type converts the value on the PHP side only, so that a bound value has to
 * go through the type to match what is stored.
 *
 * {@see \Doctrine\Tests\DbalTypes\Rot13Type}
 */
#[Table(name: 'customtype_inferred_parameters')]
#[Entity]
class CustomTypeInferredParameter
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    public int|null $id = null;

    #[Column(type: 'rot13', length: 255)]
    public string $encoded;

    #[Column(type: 'upper_case_string', length: 255, nullable: true)]
    public string|null $upperCased = null;

    #[Column(type: 'datetime_immutable', nullable: true)]
    public DateTimeImmutable|null $happenedAt = null;
}
