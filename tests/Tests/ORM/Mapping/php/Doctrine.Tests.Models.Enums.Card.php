<?php

declare(strict_types=1);

use Doctrine\Tests\Models\Enums\Suit;

$metadata->mapField(
    [
        'id' => true,
        'fieldName' => 'id',
        'type' => 'integer',
    ]
);
$metadata->mapField(
    [
        'fieldName' => 'suit',
        'type' => 'string',
        'enumType' => Suit::class,
    ]
);
