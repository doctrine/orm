<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Query;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsComment;

class MergeSplHashConflict extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testMergeSplHashConflict()
    {
        // Create
        $user = new CmsUser;
        $hash = spl_object_hash($user);

        $mergedUser = $this->_em->merge($user);
        unset($user);

        // As $user is unset, $hash could be reused by spl_object_hash
        $this->assertEquals([], $this->_em->getUnitOfWork()->getOriginalEntityDataByOid($hash));
    }
}
