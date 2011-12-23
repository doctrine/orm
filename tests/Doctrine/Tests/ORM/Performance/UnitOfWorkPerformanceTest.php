<?php

namespace Doctrine\Tests\ORM\Performance;

require_once __DIR__ . '/../../TestInit.php';

use Doctrine\Tests\Models\CMS\CmsUser;

/**
 * Description of InsertPerformanceTest
 *
 * @author robo
 * @group performance
 */
class UnitOfWorkPerformanceTest extends \Doctrine\Tests\OrmPerformanceTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testComputeChanges()
    {
        $n = 100;

        $users = array();
        for ($i=1; $i<=$n; ++$i) {
            $user = new CmsUser;
            $user->status = 'user';
            $user->username = 'user' . $i;
            $user->name = 'Mr.Smith-' . $i;
            $this->_em->persist($user);
            $users[] = $user;
        }
        $this->_em->flush();


        foreach ($users AS $user) {
            $user->status = 'other';
            $user->username = $user->username . '++';
            $user->name = str_replace('Mr.', 'Mrs.', $user->name);
        }

        $s = microtime(true);
        $this->_em->flush();
        $e = microtime(true);

        echo ' Compute ChangeSet '.$n.' objects in ' . ($e - $s) . ' seconds' . PHP_EOL;
    }
}