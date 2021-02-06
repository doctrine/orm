<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\ORM\Query;

class GH5202Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testReadOnlyQueryHint()
    {
        $user = new CmsUser();
        $user->name = "beberlei";
        $user->status = "active";
        $user->username = "beberlei";

        $this->_em->persist($user);

        $this->_em->flush();
        $this->_em->clear();

        $dql = "SELECT u FROM Doctrine\\Tests\Models\CMS\CmsUser u";

        $query = $this->_em->createQuery($dql);
        $query->setHint(Query::HINT_READ_ONLY, true);

        $user = $query->getSingleResult();

        $this->assertTrue($this->_em->getUnitOfWork()->isReadOnly($user));
    }

    public function testOnlyQueryHintForProxy()
    {
        $user = new CmsUser();
        $user->name = "beberlei";
        $user->status = "active";
        $user->username = "beberlei";

        $this->_em->persist($user);

        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->getReference(CmsUser::class, $user->id);

        $dql = "SELECT u FROM Doctrine\\Tests\Models\CMS\CmsUser u";

        $query = $this->_em->createQuery($dql);
        $query->setHint(Query::HINT_READ_ONLY, true);

        $user = $query->getSingleResult();

        $this->assertTrue($this->_em->getUnitOfWork()->isReadOnly($user));
    }
}
