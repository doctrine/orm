<?php

namespace Shitty\Tests\ORM\Functional\Ticket;

use Shitty\Common\Collections\ArrayCollection;

/**
 * @group DDC-1050
 */
class DDC1050Test extends \Shitty\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->markTestSkipped('performance skipped');

        $this->useModelSet('cms');

        parent::setUp();
    }

    public function testPerformance()
    {
        for ($i = 2; $i < 10000; ++$i) {
            $user = new \Shitty\Tests\Models\CMS\CmsUser();
            $user->status = 'developer';
            $user->username = 'jwage'+$i;
            $user->name = 'Jonathan';
            $this->_em->persist($user);
        }
        $this->_em->flush();
        $this->_em->clear();

        $s = microtime(true);
        $users = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser')->findAll();
        $e = microtime(true);
        echo __FUNCTION__ . " - " . ($e - $s) . " seconds" . PHP_EOL;
    }
}
