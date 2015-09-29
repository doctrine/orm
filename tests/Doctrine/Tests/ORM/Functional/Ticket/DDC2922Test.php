<?php


namespace Doctrine\Tests\ORM\Functional\Ticket;


use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsEmail;
use Doctrine\Tests\Models\CMS\CmsUser;

class DDC2922Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    /**
     * Unlike next test, this one demonstrates that the problem does
     * not necessarily reproduce if all the pieces are being flushed together.
     *
     * @group DDC-2922
     */
    public function testNewAssociatedEntityWorksWithJustOnePathIfAllPartsNew()
    {

        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin E.";
        $user->status = 'active';

        $email = new CmsEmail();
        $email->email = "nobody@example.com";
        $email->user = $user;

        $address = new CmsAddress();
        $address->city = "Bonn";
        $address->zip = "12354";
        $address->country = "Germany";
        $address->street = "somestreet";
        $address->user = $user;

        $this->_em->persist($email);
        $this->_em->persist($address);

        $this->_em->flush();

    }

    /**
     * This test exhibits the bug describe in the ticket, where an object that
     * ought to be reachable causes errors.
     *
     * @group DDC-2922
     */
    public function testNewAssociatedEntityWorksWithJustOnePath()
    {

        /**
         * First we persist and flush an e-mail with no user. Having the
         * "cascading path" involve a non-new object seems to be important to
         * reproducing the bug.
         */
        $email = new CmsEmail();
        $email->email = "nobody@example.com";
        $email->user = null;

        $this->_em->persist($email);
        $this->_em->flush(); // Flush before introducing CmsUser

        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin E.";
        $user->status = 'active';

        $email->user = $user;

        /**
         * Note that we have NOT directly persisted the CmsUser, and CmsAddress
         * does NOT have cascade-persist.
         *
         * However, CmsEmail *does* have a cascade-persist, which ought to
         * allow us to save the CmsUser anyway through that connection.
         */
        $address = new CmsAddress();
        $address->city = "Bonn";
        $address->zip = "12354";
        $address->country = "Germany";
        $address->street = "somestreet";
        $address->user = $user;

        $this->_em->persist($address);
        try {
            $this->_em->flush();
        } catch (ORMInvalidArgumentException $e) {
            if (strpos($e->getMessage(), 'not configured to cascade persist operations') !== FALSE) {
                $this->fail($e);
            }
            throw $e;
        }
    }
}