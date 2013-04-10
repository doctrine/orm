<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\Models\Generic\BooleanModel;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * Class DDC1048Test
 * @author Luis Cordova <cordoval@gmail.com>
 * @package Doctrine\Tests\ORM\Functional\Ticket
 */
class DDC1048Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('generic');
        parent::setUp();
    }

    /**
     * @group DDC-1048
     */
    public function testBugOnDQLForFalseValue()
    {
        $true = new BooleanModel();
        $true->booleanField = true;

        $false = new BooleanModel();
        $false->booleanField = false;

        $this->_em->persist($true);
        $this->_em->persist($false);
        $this->_em->flush();
        $this->_em->clear();

        $qb = $this->_em->createQueryBuilder()
            ->select('x')
            ->from('Doctrine\Tests\Models\Generic\BooleanModel', 'x')
        ;

        $false = $qb
            ->where($qb->expr()->andX(
                $qb->expr()->eq('x.booleanField', false),
                $qb->expr()->eq('x.booleanField', false)
            ))
            ->orderBy('x.booleanField', 'DESC')
            ->getQuery()
            ->execute()
        ;

        $true = $qb
            ->where($qb->expr()->andX(
                $qb->expr()->eq('x.booleanField', true),
                $qb->expr()->eq('x.booleanField', true)
            ))
            ->orderBy('x.booleanField', 'DESC')
            ->getQuery()
            ->execute()
        ;

        $this->assertInstanceOf('Doctrine\Tests\Models\Generic\BooleanModel', $true, "True model not found");
        $this->assertTrue($true->booleanField, "True Boolean Model should be true.");

        $this->assertInstanceOf('Doctrine\Tests\Models\Generic\BooleanModel', $false, "False model not found");
        $this->assertFalse($false->booleanField, "False Boolean Model should be false.");
    }
}