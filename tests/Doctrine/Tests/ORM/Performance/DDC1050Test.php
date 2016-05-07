<?php

namespace Doctrine\Tests\ORM\Performance;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group performance
 * @group DDC-1050
 */
class DDC1050Test extends OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('cms');

        parent::setUp();
    }

    public function testPerformance()
    {
        for ($i = 2; $i < 10000; ++$i) {
            $user = new CmsUser();

            $user->status = 'developer';
            $user->username = 'jwage'.$i;
            $user->name = 'Jonathan';

            $this->_em->persist($user);
        }

        $this->_em->flush();
        $this->_em->clear();

        $s = microtime(true);

        $this->_em->getRepository(CmsUser::class)->findAll();

        $e = microtime(true);

        echo __FUNCTION__ . " - " . ($e - $s) . " seconds" . PHP_EOL;
    }
}
