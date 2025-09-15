<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Console\Command\Debug\Fixtures;

class BazListener
{
    public function postPersists(): void
    {
    }
}
