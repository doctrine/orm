<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ResolveTargetOneToOne;

class BaseVendor implements VendorInterface
{
    protected int $id;

    protected string $name;

    protected Profile|null $profile = null;

    public function getId(): int
    {
        return $this->id;
    }
}
