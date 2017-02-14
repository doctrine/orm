<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;

/**
 * @group DDC-1400
 */
class DDC1400Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(DDC1400Article::class),
                    $this->em->getClassMetadata(DDC1400User::class),
                    $this->em->getClassMetadata(DDC1400UserState::class),
                ]
            );
        } catch (\Exception $ignored) {
        }
    }

    public function testFailingCase()
    {
        $article = new DDC1400Article;
        $user1 = new DDC1400User;
        $user2 = new DDC1400User;

        $this->em->persist($article);
        $this->em->persist($user1);
        $this->em->persist($user2);
        $this->em->flush();

        $userState1 = new DDC1400UserState;
        $userState1->article = $article;
        $userState1->user = $user1;

        $userState2 = new DDC1400UserState;
        $userState2->article = $article;
        $userState2->user = $user2;

        $this->em->persist($userState1);
        $this->em->persist($userState2);
        $this->em->flush();
        $this->em->clear();

        $user1 = $this->em->getReference(DDC1400User::class, $user1->id);

        $this->em->createQuery('SELECT a, s FROM ' . DDC1400Article::class . ' a JOIN a.userStates s WITH s.user = :activeUser')
                  ->setParameter('activeUser', $user1)
                  ->getResult();

        $queryCount = $this->getCurrentQueryCount();

        $this->em->flush();

        self::assertSame($queryCount, $this->getCurrentQueryCount(), 'No query should be executed during flush in this case');
    }
}

/**
 * @ORM\Entity
 */
class DDC1400Article
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;

    /**
     * @ORM\OneToMany(targetEntity="DDC1400UserState", mappedBy="article", indexBy="user", fetch="EXTRA_LAZY")
     */
    public $userStates;
}

/**
 * @ORM\Entity
 */
class DDC1400User
{

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;

    /**
     * @ORM\OneToMany(targetEntity="DDC1400UserState", mappedBy="user", indexBy="article", fetch="EXTRA_LAZY")
     */
    public $userStates;
}

/**
 * @ORM\Entity
 */
class DDC1400UserState
{
    /**
      * @ORM\Id
     *  @ORM\ManyToOne(targetEntity="DDC1400Article", inversedBy="userStates")
     */
    public $article;

    /**
      * @ORM\Id
     *  @ORM\ManyToOne(targetEntity="DDC1400User", inversedBy="userStates")
     */
    public $user;
}
