<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Encrypt;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Encrypt;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 * One nullable encrypted field per DBAL built-in type, for round-trip coverage of the
 * conversion pipeline (plaintext -> DB value -> encrypt -> decrypt -> PHP value).
 */
#[Table(name: 'encrypt_types')]
#[Entity]
class EncryptTypesEntity
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: Types::INTEGER)]
    public int|null $id = null;

    #[Column(type: Types::ASCII_STRING, length: 255, nullable: true)]
    #[Encrypt]
    public mixed $asciiStringValue = null;

    #[Column(type: Types::BIGINT, nullable: true)]
    #[Encrypt]
    public mixed $bigintValue = null;

    #[Column(type: Types::BINARY, nullable: true)]
    #[Encrypt]
    public mixed $binaryValue = null;

    #[Column(type: Types::BLOB, nullable: true)]
    #[Encrypt]
    public mixed $blobValue = null;

    #[Column(type: Types::BOOLEAN, nullable: true)]
    #[Encrypt]
    public mixed $booleanValue = null;

    #[Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Encrypt]
    public mixed $dateValue = null;

    #[Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Encrypt]
    public mixed $dateImmutableValue = null;

    #[Column(type: Types::DATEINTERVAL, nullable: true)]
    #[Encrypt]
    public mixed $dateintervalValue = null;

    #[Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Encrypt]
    public mixed $datetimeValue = null;

    #[Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Encrypt]
    public mixed $datetimeImmutableValue = null;

    #[Column(type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    #[Encrypt]
    public mixed $datetimetzValue = null;

    #[Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Encrypt]
    public mixed $datetimetzImmutableValue = null;

    #[Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Encrypt]
    public mixed $decimalValue = null;

    #[Column(type: Types::NUMBER, precision: 10, scale: 2, nullable: true)]
    #[Encrypt]
    public mixed $numberValue = null;

    #[Column(type: Types::FLOAT, nullable: true)]
    #[Encrypt]
    public mixed $floatValue = null;

    #[Column(type: Types::ENUM, nullable: true, options: ['values' => ['hearts', 'spades']])]
    #[Encrypt]
    public mixed $enumValue = null;

    #[Column(type: Types::GUID, nullable: true)]
    #[Encrypt]
    public mixed $guidValue = null;

    #[Column(type: Types::INTEGER, nullable: true)]
    #[Encrypt]
    public mixed $integerValue = null;

    #[Column(type: Types::JSON, nullable: true)]
    #[Encrypt]
    public mixed $jsonValue = null;

    #[Column(type: Types::JSON_OBJECT, nullable: true)]
    #[Encrypt]
    public mixed $jsonObjectValue = null;

    #[Column(type: Types::JSONB, nullable: true)]
    #[Encrypt]
    public mixed $jsonbValue = null;

    #[Column(type: Types::JSONB_OBJECT, nullable: true)]
    #[Encrypt]
    public mixed $jsonbObjectValue = null;

    #[Column(type: Types::SIMPLE_ARRAY, nullable: true)]
    #[Encrypt]
    public mixed $simpleArrayValue = null;

    #[Column(type: Types::SMALLFLOAT, nullable: true)]
    #[Encrypt]
    public mixed $smallfloatValue = null;

    #[Column(type: Types::SMALLINT, nullable: true)]
    #[Encrypt]
    public mixed $smallintValue = null;

    #[Column(type: Types::STRING, length: 255, nullable: true)]
    #[Encrypt]
    public mixed $stringValue = null;

    #[Column(type: Types::TEXT, nullable: true)]
    #[Encrypt]
    public mixed $textValue = null;

    #[Column(type: Types::TIME_MUTABLE, nullable: true)]
    #[Encrypt]
    public mixed $timeValue = null;

    #[Column(type: Types::TIME_IMMUTABLE, nullable: true)]
    #[Encrypt]
    public mixed $timeImmutableValue = null;
}
