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
     * @group DDC-2922
     */
    public function testNewAssociatedEntityWorksWithJustOnePath()
    {

        /**
         * First we persist and flush an e-mail with no user. This seems
         * Save an un-owned email with no user. This seems to
         * matter for reproducing the bug
         */
        $mail = new CmsEmail();
        $mail->email = "nobody@example.com";
        $mail->user = null;

        $this->_em->persist($mail);
        $this->_em->flush();

        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin E.";
        $user->status = 'active';

        $mail->user = $user;

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
        try{
            $this->_em->flush();
        }catch(ORMInvalidArgumentException $e){
            if(strpos($e->getMessage(),'not configured to cascade persist operations') !== FALSE) {
                $this->fail($e);
            }
            throw $e;
        }


    }
}