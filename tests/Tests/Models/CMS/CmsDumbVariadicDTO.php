<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

class CmsDumbVariadicDTO
{
    private array $values = [];

    public function __construct(...$args)
    {
        foreach ($args as $key => $val) {
            $this->values[$key] = $val;
        }
    }

    public function __get(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }
}
