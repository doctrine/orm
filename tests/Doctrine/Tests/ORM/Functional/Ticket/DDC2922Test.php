<?php
declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\ToolsException;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC2922Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
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
        } catch (ToolsException $ignored) {
        }
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
