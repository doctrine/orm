<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Query;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

class MergeSplHashConflictTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    /**
     * @test
     */
    public function testMergeSplHashConflict()
    {
        // Create
        $user = new CmsUser;
        $hash = spl_object_hash($user);

        $mergedUser = $this->_em->merge($user);
        unset($user);

        $uow = $this->_em->getUnitOfWork();
        $reflexion = new \ReflectionClass(get_class($uow));
        $property = $reflexion->getProperty('originalEntityData');
        $property->setAccessible(true);
        $originalEntityData = $property->getValue($uow);

        // As $user is unset, $hash could be reused by spl_object_hash
        $this->assertArrayNotHasKey($hash, $originalEntityData);
    }
}
