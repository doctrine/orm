<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Query;

/**
 * @group DDC-2655
 */
class DDC2655Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testSingleScalarOneOrNullResult()
    {
        $query = $this->_em->createQuery("SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.username = 'happy_doctrine_user'");
        $this->assertNull($query->getOneOrNullResult(Query::HYDRATE_SINGLE_SCALAR));
    }
}
