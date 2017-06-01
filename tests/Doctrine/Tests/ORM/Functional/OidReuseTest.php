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
        $uow = $this->_em->getUnitOfWork();
        $reflexion = new \ReflectionClass(get_class($uow));
        $originalEntityDataProperty = $reflexion->getProperty('originalEntityData');
        $originalEntityDataProperty->setAccessible(true);

        $user = new CmsUser();
        $oid = spl_object_hash($user);
        $this->_em->merge($user);

        $user = null;

        $this->assertArrayNotHasKey($oid, $originalEntityDataProperty->getValue($uow));

        $user = new CmsUser();
        $this->_em->persist($user);
    }

}