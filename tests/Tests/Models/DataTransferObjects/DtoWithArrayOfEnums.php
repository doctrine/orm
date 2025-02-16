<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DataTransferObjects;

use Doctrine\Tests\Models\Enums\Unit;

final class DtoWithArrayOfEnums
{
    /** @var Unit[] */
    public $supportedUnits;

    /** @param Unit[] $supportedUnits */
    public function __construct(array $supportedUnits)
    {
        $this->supportedUnits = $supportedUnits;
    }
}
