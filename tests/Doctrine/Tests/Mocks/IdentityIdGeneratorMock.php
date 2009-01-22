<?php

namespace Doctrine\Tests\Mocks;

/**
 * Description of Doctrine_IdentityIdGeneratorMock
 *
 * @author robo
 */
class IdentityIdGeneratorMock extends \Doctrine\ORM\Id\IdentityGenerator
{
    private $_mockPostInsertId;

    public function setMockPostInsertId($id) {
        $this->_mockPostInsertId = $id;
    }
}

