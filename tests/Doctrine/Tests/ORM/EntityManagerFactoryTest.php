<?php

namespace Doctrine\Tests\ORM;

use Doctrine\Tests\OrmTestCase;
use Doctrine\ORM\EntityManagerFactory;

class EntityManagerFactoryTest extends OrmTestCase
{
    public function testCreateEntityManager()
    {
        $emf = new EntityManagerFactory();

        $config = $this->getMockConfiguration();
        $conn = $this->getMockConnection(null, $config, null);

        $this->assertInstanceOf('Doctrine\ORM\EntityManagerInterface', $emf->create(
            $conn,
            $config,
            null
        ));
    }
}
