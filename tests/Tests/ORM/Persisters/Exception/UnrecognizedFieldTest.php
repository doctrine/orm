<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Persisters\Exception;

use Doctrine\ORM\Persisters\Exception\UnrecognizedField;
use Doctrine\Tests\Models\Taxi\Car;
use PHPUnit\Framework\TestCase;

class UnrecognizedFieldTest extends TestCase
{
    public function testByFullyQualifiedName(): void
    {
        static::expectException(UnrecognizedField::class);
        static::expectExceptionMessage('Unrecognized field: Doctrine\Tests\Models\Taxi\Car::$color');

        throw UnrecognizedField::byFullyQualifiedName(Car::class, 'color');
    }
}
