<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH5562Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->enableSecondLevelCache();

        parent::setUp();

        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(GH5562User::class),
                $this->em->getClassMetadata(GH5562Manager::class),
                $this->em->getClassMetadata(GH5562Merchant::class),
            ]
        );
    }

    /**
     * @group 5562
     */
    public function testCacheShouldBeUpdatedWhenAssociationChanges()
    {
        $manager = new GH5562Manager();
        $merchant = new GH5562Merchant();

        $manager->username = 'username';
        $manager->merchant = $merchant;
        $merchant->manager = $manager;

        $merchant->name = 'Merchant';

        $this->em->persist($merchant);
        $this->em->persist($manager);
        $this->em->flush();
        $this->em->clear();

        $merchant = $this->em->find(GH5562Merchant::class, $merchant->id);

        $merchant->name = mt_rand();
        $merchant->manager->username = 'usernameUPDATE';

        $this->em->flush();
        $this->em->clear();

        $merchant = $this->em->find(GH5562Merchant::class, $merchant->id);

        self::assertEquals('usernameUPDATE', $merchant->manager->username);
    }
}

/**
 * @ORM\Entity
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class GH5562Merchant
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    public $id;

    /**
     * @var GH5562Manager
     *
     * @ORM\OneToOne(targetEntity=GH5562Manager::class, mappedBy="merchant")
     * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
     */
    public $manager;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    public $name;
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorMap({"MANAGER"  = GH5562Manager::class})
 */
abstract class GH5562User
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    public $id;
}

/**
 * @ORM\Entity
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class GH5562Manager extends GH5562User
{

    /**
     * @var string
     *
     * @ORM\Column
     */
    public $username;

    /**
     * @var GH5562Merchant
     *
     * @ORM\OneToOne(targetEntity=GH5562Merchant::class, inversedBy="manager")
     */
    public $merchant;
}
