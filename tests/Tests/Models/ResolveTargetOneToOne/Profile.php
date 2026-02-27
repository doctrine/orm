<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ResolveTargetOneToOne;

class Profile
{
    protected int $id;

    protected string $companyName;

    protected VendorInterface|null $vendor = null;

    public function getId(): int
    {
        return $this->id;
    }
}
