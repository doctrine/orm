<?php

namespace Doctrine\Tests\Mocks;

/**
 * Mock class for IdentityGenerator.
 */
class IdentityIdGeneratorMock extends \Doctrine\ORM\Id\IdentityGenerator
{
    private $_mockPostInsertId;

    public function setMockPostInsertId($id)
    {
        $this->_mockPostInsertId = $id;
    }
}
