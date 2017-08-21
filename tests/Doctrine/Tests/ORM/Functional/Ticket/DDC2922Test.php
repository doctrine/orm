<?php
declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\Tools\ToolsException;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsEmail;
use Doctrine\Tests\Models\CMS\CmsUser;

class DDC2922Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array_map(
                function (string $className) : ClassMetadata {
                    return $this->_em->getClassMetadata($className);
                },
                [
                    DDC2922CascadePersistedEntity::class,
                    DDC2922EntityWithCascadingAssociation::class,
                    DDC2922EntityWithNonCascadingAssociation::class,
                ]
            ));
        } catch (ToolsException $ignored) {}
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

        // Verify the flush succeeded
        $this->assertEquals($email, $this->_em->find(get_class($email),$email->id));
        $this->assertEquals($address, $this->_em->find(get_class($address),$address->id));
        $this->assertEquals($user, $this->_em->find(get_class($user),$user->id));

    }

    /**
     * This test exhibits the bug describe in the ticket, where an object that
     * ought to be reachable causes errors.
     *
     * @group DDC-2922
     */
    public function testNewAssociatedEntityWorksWithJustOnePath()
    {
        self::markTestSkipped();
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

        // Verify the flushes succeeded
        $this->assertEquals($email, $this->_em->find(get_class($email),$email->id));
        $this->assertEquals($address, $this->_em->find(get_class($address),$address->id));
        $this->assertEquals($user, $this->_em->find(get_class($user),$user->id));

    }

    /**
     * Unlike next test, this one demonstrates that the problem does
     * not necessarily reproduce if all the pieces are being flushed together.
     *
     * @group DDC-2922
     */
    public function testNewAssociatedEntityWorksWithJustOnePath__()
    {
        $cascadePersisted = new DDC2922CascadePersistedEntity();
        $cascading        = new DDC2922EntityWithCascadingAssociation();
        $nonCascading     = new DDC2922EntityWithNonCascadingAssociation();

        // First we persist and flush a DDC2922EntityWithCascadingAssociation with
        // the cascading association not set. Having the "cascading path" involve
        // a non-new object is important to show that the ORM should be considering
        // cascades across entity changesets in subsequent flushes.
        $cascading->cascaded = $cascadePersisted;
        $nonCascading->cascaded = $cascadePersisted;

        $this->_em->persist($cascading);
        $this->_em->persist($nonCascading);
        $this->_em->flush();

        // @TODO assert persistence on both associations
    }


    /**
     * This test exhibits the bug describe in the ticket, where an object that
     * ought to be reachable causes errors.
     *
     * @group DDC-2922
     */
    public function testNewAssociatedEntityWorksWithJustOnePath_()
    {
        $cascadePersisted = new DDC2922CascadePersistedEntity();
        $cascading        = new DDC2922EntityWithCascadingAssociation();
        $nonCascading     = new DDC2922EntityWithNonCascadingAssociation();

        // First we persist and flush a DDC2922EntityWithCascadingAssociation with
        // the cascading association not set. Having the "cascading path" involve
        // a non-new object is important to show that the ORM should be considering
        // cascades across entity changesets in subsequent flushes.
        $cascading->cascaded = null;

        $this->_em->persist($cascading);
        $this->_em->flush();

        // Note that we have NOT directly persisted the DDC2922CascadePersistedEntity,
        // and DDC2922EntityWithNonCascadingAssociation does NOT have a configured
        // cascade-persist.
        $nonCascading->nonCascaded = $cascadePersisted;

        // However, DDC2922EntityWithCascadingAssociation *does* have a cascade-persist
        // association, which ought to allow us to save the DDC2922CascadePersistedEntity
        // anyway through that connection.
        $cascading->cascaded = $cascadePersisted;

        $this->_em->persist($nonCascading);
        $this->_em->flush();

        // @TODO assert persistence on both associations
    }
}

/** @Entity */
class DDC2922CascadePersistedEntity
{
    /** @Id @Column(type="string") @GeneratedValue(strategy="NONE") */
    private $id;

    public function __construct()
    {
        $this->id = uniqid(self::class, true);
    }
}

/** @Entity */
class DDC2922EntityWithCascadingAssociation
{
    /** @Id @Column(type="string") @GeneratedValue(strategy="NONE") */
    private $id;

    /** @ManyToOne(targetEntity=DDC2922CascadePersistedEntity::class, cascade={"persist"}) */
    public $cascaded;

    public function __construct()
    {
        $this->id = uniqid(self::class, true);
    }
}

/** @Entity */
class DDC2922EntityWithNonCascadingAssociation
{
    /** @Id @Column(type="string") @GeneratedValue(strategy="NONE") */
    private $id;

    /** @ManyToOne(targetEntity=DDC2922CascadePersistedEntity::class) */
    public $nonCascaded;

    public function __construct()
    {
        $this->id = uniqid(self::class, true);
    }
}
