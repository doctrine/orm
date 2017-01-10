<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

class DDC960Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DDC960Root::class),
                $this->em->getClassMetadata(DDC960Child::class)
                ]
            );
        } catch(\Exception $e) {

        }
    }

    /**
     * @group DDC-960
     */
    public function testUpdateRootVersion()
    {
        $child = new DDC960Child('Test');
        $this->em->persist($child);
        $this->em->flush();

        $child->setName("Test2");

        $this->em->flush();

        self::assertEquals(2, $child->getVersion());
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorMap({
 *  "root" = "DDC960Root",
 *  "child" = "DDC960Child"
 * })
 */
class DDC960Root
{
    /**
     * @Id @GeneratedValue @Column(type="integer")
     */
    private $id;

    /**
     * @Column(type="integer") @Version
     */
    private $version;

    public function getId()
    {
        return $this->id;
    }

    public function getVersion()
    {
        return $this->version;
    }
}

/**
 * @Entity
 */
class DDC960Child extends DDC960Root
{
    /**
     * @column(type="string")
     * @var string
     */
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}
