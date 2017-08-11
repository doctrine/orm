<?php
/**
 *
 * User: Uladzimir Struts <Sysaninster@gmail.com>
 * Date: 11.08.2017
 * Time: 12:28
 */

namespace Doctrine\Tests\ORM\Functional\Ticket;


use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Tests\Models\DDC6613\Phone;
use Doctrine\Tests\Models\DDC6613\User;


class DDC6613Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    public function setUp()
    {
        parent::setUp();

        try {
            $this->setUpEntitySchema(
                [
                    Phone::class,
                    User::class
                ]
            );
        } catch (SchemaException $e) {
        }
    }

    public function testFail()
    {
        $user = new User();
        $user->id = 1;
        $this->_em->persist($user);
        $this->_em->flush();

        $this->_em->clear();

        /** @var User $user */
        $user = $this->_em->find(User::class, 1);
        $phone1 = new Phone();
        $phone1->id = 1;
        $user->phones->add($phone1);
        $this->_em->persist($phone1);
        $this->_em->flush();

        $phone2 = new Phone();
        $phone2->id = 2;
        $user->phones->add($phone2);
        $this->_em->persist($phone2);
        $user->phones->toArray();
        $this->_em->flush();
    }

}