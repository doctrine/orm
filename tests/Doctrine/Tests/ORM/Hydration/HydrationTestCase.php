<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\Tests\OrmTestCase;

class HydrationTestCase extends OrmTestCase
{
    protected $_em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->_em = $this->getTestEntityManager();
    }
}
