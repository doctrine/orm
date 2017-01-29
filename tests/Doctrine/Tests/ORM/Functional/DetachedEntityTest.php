<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Description of DetachedEntityTest
 *
 * @author robo
 */
class DetachedEntityTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    /**
     * @group DDC-203
     */
    public function testDetachedEntityThrowsExceptionOnFlush()
    {
        $ph = new CmsPhonenumber();
        $ph->phonenumber = '12345';
        $this->em->persist($ph);
        $this->em->flush();
        $this->em->clear();
        $this->em->persist($ph);
        try {
            $this->em->flush();
            $this->fail();
        } catch (\Exception $expected) {}
    }


}

