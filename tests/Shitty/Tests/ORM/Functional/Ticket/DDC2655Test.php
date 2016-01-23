<?php

namespace Shitty\Tests\ORM\Functional\Ticket;

use Shitty\ORM\Query;

/**
 * @group DDC-2655
 */
class DDC2655Test extends \Shitty\Tests\OrmFunctionalTestCase
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
