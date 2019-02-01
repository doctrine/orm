<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH7589Test extends OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(GH7589User::class),
                $this->_em->getClassMetadata(GH7589Article::class),
            ]
        );
    }

    public function testPersistedThenRemovedEntityIsRemoved()
    {
        $user    = new GH7589User(1);
        $article = new GH7589Article(1, $user);

        $this->_em->persist($user);
        $this->_em->persist($article);

        $this->_em->remove($article);

        $this->_em->flush();

        $this->assertNull($this->_em->find(GH7589Article::class, 1), 'Entity should not be persisted.');
        $this->assertCount(0, $user->getArticles(), 'Entity should be removed from inverse association collection.');
    }
}

/**
 * @Entity
 */
class GH7589User
{
    /**
     * @Id
     * @Column(type="integer")
     */
    private $id;

    /** @OneToMany(targetEntity="GH7589Article", mappedBy="user") */
    private $articles;

    public function __construct(int $id)
    {
        $this->id       = $id;
        $this->articles = new ArrayCollection();
    }

    public function addArticle(GH7589Article $article) : void
    {
        $this->articles->add($article);
    }

    public function getArticles() : array
    {
        return $this->articles->getValues();
    }
}

/**
 * @Entity
 */
class GH7589Article
{
    /**
     * @Id
     * @Column(type="integer")
     */
    private $id;

    /** @ManyToOne(targetEntity="GH7589User", inversedBy="articles") */
    private $user;

    public function __construct(int $id, GH7589User $user)
    {
        $this->id   = $id;
        $this->user = $user;
        $this->user->addArticle($this);
    }
}
