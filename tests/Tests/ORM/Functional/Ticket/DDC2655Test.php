<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Query;
use Doctrine\Tests\OrmFunctionalTestCase;

/** @group DDC-2655 */
class DDC2655Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();
    }

    public function testSingleScalarOneOrNullResult(): void
    {
        $query = $this->_em->createQuery("SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.username = 'happy_doctrine_user'");
        self::assertNull($query->getOneOrNullResult(Query::HYDRATE_SINGLE_SCALAR));
    }
}
