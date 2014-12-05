<?php

namespace Doctrine\Tests\Models\Mapping;

use Doctrine\ORM\Id\AbstractIdGenerator;
use Doctrine\ORM\EntityManager;

class CustomIdGenerator extends AbstractIdGenerator
{
    public function generate(EntityManager $em, $entity)
    {

    }
}
