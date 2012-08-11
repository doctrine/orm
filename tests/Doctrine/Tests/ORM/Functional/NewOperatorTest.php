<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\DBAL\Connection;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsEmail;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;

require_once __DIR__ . '/../../TestInit.php';

/**
 * @group DDC-1574
 */
class NewOperatorTest extends \Doctrine\Tests\OrmFunctionalTestCase
{

    /**
     * @var array
     */
    private $fixtures;
    
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();

        $this->loadFixtures();
    }

    private function loadFixtures()
    {
        $u1 = new CmsUser;
        $u2 = new CmsUser;
        $u3 = new CmsUser;

        $u1->setEmail(new CmsEmail());
        $u1->setAddress(new CmsAddress());
        $u1->addPhonenumber(new CmsPhonenumber());

        $u2->setEmail(new CmsEmail());
        $u2->setAddress(new CmsAddress());
        $u2->addPhonenumber(new CmsPhonenumber());
        $u2->addPhonenumber(new CmsPhonenumber());

        $u3->setEmail(new CmsEmail());
        $u3->setAddress(new CmsAddress());
        $u3->addPhonenumber(new CmsPhonenumber());
        $u3->addPhonenumber(new CmsPhonenumber());
        $u3->addPhonenumber(new CmsPhonenumber());

        $u1->name               = 'Test 1';
        $u1->username           = '1test';
        $u1->status             = 'developer';
        $u1->email->email       = 'email@test1.com';
        $u1->address->zip       = '111111111';
        $u1->address->city      = 'Some City 1';
        $u1->address->country   = 'Some Country 2';
        $u1->phonenumbers[0]->phonenumber = "(11) 1111-1111";

        $u2->name               = 'Test 2';
        $u2->username           = '2test';
        $u2->status             = 'developer';
        $u2->email->email       = 'email@test2.com';
        $u2->address->zip       = '222222222';
        $u2->address->city      = 'Some City 2';
        $u2->address->country   = 'Some Country 2';
        $u2->phonenumbers[0]->phonenumber = "(22) 1111-1111";
        $u2->phonenumbers[1]->phonenumber = "(22) 2222-2222";

        $u3->name               = 'Test 3';
        $u3->username           = '3test';
        $u3->status             = 'developer';
        $u3->email->email       = 'email@test3.com';
        $u3->address->zip       = '33333333';
        $u3->address->city      = 'Some City 3';
        $u3->address->country   = 'Some Country 3';
        $u3->phonenumbers[0]->phonenumber = "(33) 1111-1111";
        $u3->phonenumbers[1]->phonenumber = "(33) 2222-2222";
        $u3->phonenumbers[2]->phonenumber = "(33) 3333-3333";

        $this->_em->persist($u1);
        $this->_em->persist($u2);
        $this->_em->persist($u3);

        $this->_em->flush();
        $this->_em->clear();

        $this->fixtures = array($u1, $u2, $u3);
    }

    public function testShouldSupportsBasicUsage()
    {
        $dql = "
            SELECT
                new Doctrine\Tests\Models\CMS\CmsUserDTO(
                    u.name,
                    e.email,
                    a.city
                )
            FROM 
                Doctrine\Tests\Models\CMS\CmsUser u
            JOIN
                u.email e
            JOIN
                u.address a
            ORDER BY
                u.name";

        $query  = $this->_em->createQuery($dql);
        $result = $query->getResult();

        $this->assertCount(3, $result);

        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUserDTO', $result[0]);
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUserDTO', $result[1]);
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUserDTO', $result[2]);

        $this->assertEquals($this->fixtures[0]->name, $result[0]->name);
        $this->assertEquals($this->fixtures[1]->name, $result[1]->name);
        $this->assertEquals($this->fixtures[2]->name, $result[2]->name);
        
        $this->assertEquals($this->fixtures[0]->email->email, $result[0]->email);
        $this->assertEquals($this->fixtures[1]->email->email, $result[1]->email);
        $this->assertEquals($this->fixtures[2]->email->email, $result[2]->email);
        
        $this->assertEquals($this->fixtures[0]->address->city, $result[0]->address);
        $this->assertEquals($this->fixtures[1]->address->city, $result[1]->address);
        $this->assertEquals($this->fixtures[2]->address->city, $result[2]->address);
    }

    public function testShouldSupportNestedOperators()
    {
        $this->markTestIncomplete();
        $dql = "
            SELECT
                new Doctrine\Tests\Models\CMS\CmsUserDTO(
                    u.name,
                    e.email,
                    new Doctrine\Tests\Models\CMS\CmsAddressDTO(
                        a.country,
                        a.city,
                        a.zip
                    )
                )
            FROM
                Doctrine\Tests\Models\CMS\CmsUser u
            JOIN
                u.email e
            JOIN
                u.address a
            ORDER BY
                u.name";

        $query  = $this->_em->createQuery($dql);
        $result = $query->getResult();

        $this->assertCount(3, $result);

        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUserDTO', $result[0]);
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUserDTO', $result[1]);
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUserDTO', $result[2]);

    }

    public function testShouldSupportAggregateFunctions()
    {
        $dql = "
            SELECT
                new Doctrine\Tests\Models\CMS\CmsUserDTO(
                    u.name,
                    e.email,
                    a.city,
                    COUNT(p)
                )
            FROM
                Doctrine\Tests\Models\CMS\CmsUser u
            JOIN
                u.email e
            JOIN
                u.address a
            JOIN
                u.phonenumbers p
            GROUP BY
                u, e, a
            ORDER BY
                u.name";

        $query  = $this->_em->createQuery($dql);
        $result = $query->getResult();

        $this->assertCount(3, $result);

        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUserDTO', $result[0]);
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUserDTO', $result[1]);
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUserDTO', $result[2]);

        $this->assertEquals($this->fixtures[0]->name, $result[0]->name);
        $this->assertEquals($this->fixtures[1]->name, $result[1]->name);
        $this->assertEquals($this->fixtures[2]->name, $result[2]->name);

        $this->assertEquals($this->fixtures[0]->email->email, $result[0]->email);
        $this->assertEquals($this->fixtures[1]->email->email, $result[1]->email);
        $this->assertEquals($this->fixtures[2]->email->email, $result[2]->email);

        $this->assertEquals($this->fixtures[0]->address->city, $result[0]->address);
        $this->assertEquals($this->fixtures[1]->address->city, $result[1]->address);
        $this->assertEquals($this->fixtures[2]->address->city, $result[2]->address);

        $this->assertEquals(
            (count($this->fixtures[0]->phonenumbers)),
            $result[0]->phonenumbers
        );

        $this->assertEquals(
            (count($this->fixtures[1]->phonenumbers)),
            $result[1]->phonenumbers
        );

        $this->assertEquals(
            (count($this->fixtures[2]->phonenumbers)),
            $result[2]->phonenumbers
        );
    }

    public function testShouldSupportArithmeticExpression()
    {
        $dql = "
            SELECT
                new Doctrine\Tests\Models\CMS\CmsUserDTO(
                    u.name,
                    e.email,
                    a.city,
                    COUNT(p) + u.id
                )
            FROM
                Doctrine\Tests\Models\CMS\CmsUser u
            JOIN
                u.email e
            JOIN
                u.address a
            JOIN
                u.phonenumbers p
            GROUP BY
                u, e, a
            ORDER BY
                u.name";

        $query  = $this->_em->createQuery($dql);
        $result = $query->getResult();

        $this->assertCount(3, $result);

        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUserDTO', $result[0]);
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUserDTO', $result[1]);
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUserDTO', $result[2]);

        $this->assertEquals($this->fixtures[0]->name, $result[0]->name);
        $this->assertEquals($this->fixtures[1]->name, $result[1]->name);
        $this->assertEquals($this->fixtures[2]->name, $result[2]->name);

        $this->assertEquals($this->fixtures[0]->email->email, $result[0]->email);
        $this->assertEquals($this->fixtures[1]->email->email, $result[1]->email);
        $this->assertEquals($this->fixtures[2]->email->email, $result[2]->email);

        $this->assertEquals($this->fixtures[0]->address->city, $result[0]->address);
        $this->assertEquals($this->fixtures[1]->address->city, $result[1]->address);
        $this->assertEquals($this->fixtures[2]->address->city, $result[2]->address);

        $this->assertEquals(
            (count($this->fixtures[0]->phonenumbers) + $this->fixtures[0]->id),
            $result[0]->phonenumbers
        );

        $this->assertEquals(
            (count($this->fixtures[1]->phonenumbers) + $this->fixtures[1]->id),
            $result[1]->phonenumbers
        );

        $this->assertEquals(
            (count($this->fixtures[2]->phonenumbers) + $this->fixtures[2]->id),
            $result[2]->phonenumbers
        );
    }
}