<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Query;
use Doctrine\Tests\Models\CMS\CmsUser;

class MergeSplHashConflict extends \Doctrine\Tests\OrmFunctionalTestCase
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

        $this->assertTrue(false);

        // As $user is unset, $hash could be reused by spl_object_hash
        $this->assertEquals(["gdhs"], $this->_em->getUnitOfWork()->getOriginalEntityDataByOid($hash));
    }
}
