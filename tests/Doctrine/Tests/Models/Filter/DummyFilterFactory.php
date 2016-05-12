<?php

namespace Doctrine\Tests\Models\Filter;

use Doctrine\ORM\Query\Filter\FilterFactoryInterface;

class DummyFilterFactory implements FilterFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createFromName($name)
    {
    }
}
