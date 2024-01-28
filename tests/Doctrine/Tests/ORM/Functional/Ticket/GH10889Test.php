<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @see   https://github.com/doctrine/orm/issues/10889
 *
 * @group GH10889
 */
class GH10889Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH10889User::class,
            GH10889Finger::class,
            GH10889Hand::class
        );
    }

    public function testIssue(): void
    {
        $user = new GH10889User();
        $hand = new GH10889Hand($user, null);

        $this->_em->persist($user);
        $this->_em->persist($hand);
        $this->_em->flush();
        $this->_em->clear();

        /** @var list<GH10889Hand> $hands */
        $hands = $this->_em
            ->getRepository(GH10889Hand::class)
            ->createQueryBuilder('hand')
            ->leftJoin('hand.thumb', 'thumb')->addSelect('thumb')
            ->getQuery()
            ->getResult();

        $this->assertArrayHasKey(0, $hands);
        $this->assertEquals(1, $hands[0]->user->id);
        $this->assertNull($hands[0]->thumb);
    }
}

/**
 * @ORM\Entity
 */
class GH10889User
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    public $id;
}

/**
 * @ORM\Entity
 */
class GH10889Finger
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    public $id;
}

/**
 * @ORM\Entity
 */
class GH10889Hand
{
    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="GH10889User")
     *
     * @var GH10889User
     */
    public $user;

    /**
     * @ORM\ManyToOne(targetEntity="GH10889Finger")
     *
     * @var GH10889Finger|null
     */
    public $thumb;

    public function __construct(GH10889User $user, ?GH10889Finger $thumb)
    {
        $this->user  = $user;
        $this->thumb = $thumb;
    }
}
