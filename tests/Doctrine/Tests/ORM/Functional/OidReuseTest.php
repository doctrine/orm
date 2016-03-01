<?php

namespace Doctrine\Tests\ORM\Functional;


use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

class OidReuseTest extends OrmFunctionalTestCase
{

    private $userId;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testOidReuse()
    {
        $user = new CmsUser();
        $this->_em->merge($user);

        $user = null;

        $user = new CmsUser();
        $this->_em->persist($user);
    }

}