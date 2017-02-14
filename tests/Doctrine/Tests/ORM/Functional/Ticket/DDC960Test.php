<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
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
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorMap({
 *  "root" = "DDC960Root",
 *  "child" = "DDC960Child"
 * })
 */
class DDC960Root
{
    /**
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer") @ORM\Version
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
 * @ORM\Entity
 */
class DDC960Child extends DDC960Root
{
    /**
     * @ORM\Column(type="string")
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
