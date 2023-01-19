<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\Tests\OrmFunctionalTestCase;

/** @group DDC-1654 */
class DDC1654Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema(
            [
                DDC1654Post::class,
                DDC1654Comment::class,
            ]
        );
    }

    public function tearDown(): void
    {
        $conn = static::$sharedConn;
        $conn->executeStatement('DELETE FROM ddc1654post_ddc1654comment');
        $conn->executeStatement('DELETE FROM DDC1654Comment');
        $conn->executeStatement('DELETE FROM DDC1654Post');
    }

    public function testManyToManyRemoveFromCollectionOrphanRemoval(): void
    {
        $post             = new DDC1654Post();
        $post->comments[] = new DDC1654Comment();
        $post->comments[] = new DDC1654Comment();

        $this->_em->persist($post);
        $this->_em->flush();

        $post->comments->remove(0);
        $post->comments->remove(1);

        $this->_em->flush();
        $this->_em->clear();

        $comments = $this->_em->getRepository(DDC1654Comment::class)->findAll();
        self::assertCount(0, $comments);
    }

    public function testManyToManyRemoveElementFromCollectionOrphanRemoval(): void
    {
        $post             = new DDC1654Post();
        $post->comments[] = new DDC1654Comment();
        $post->comments[] = new DDC1654Comment();

        $this->_em->persist($post);
        $this->_em->flush();

        $post->comments->removeElement($post->comments[0]);
        $post->comments->removeElement($post->comments[1]);

        $this->_em->flush();
        $this->_em->clear();

        $comments = $this->_em->getRepository(DDC1654Comment::class)->findAll();
        self::assertCount(0, $comments);
    }

    /** @group DDC-3382 */
    public function testManyToManyRemoveElementFromReAddToCollectionOrphanRemoval(): void
    {
        $post             = new DDC1654Post();
        $post->comments[] = new DDC1654Comment();
        $post->comments[] = new DDC1654Comment();

        $this->_em->persist($post);
        $this->_em->flush();

        $comment = $post->comments[0];
        $post->comments->removeElement($comment);
        $post->comments->add($comment);

        $this->_em->flush();
        $this->_em->clear();

        $comments = $this->_em->getRepository(DDC1654Comment::class)->findAll();
        self::assertCount(2, $comments);
    }

    public function testManyToManyClearCollectionOrphanRemoval(): void
    {
        $post             = new DDC1654Post();
        $post->comments[] = new DDC1654Comment();
        $post->comments[] = new DDC1654Comment();

        $this->_em->persist($post);
        $this->_em->flush();

        $post->comments->clear();

        $this->_em->flush();
        $this->_em->clear();

        $comments = $this->_em->getRepository(DDC1654Comment::class)->findAll();
        self::assertCount(0, $comments);
    }

    /** @group DDC-3382 */
    public function testManyToManyClearCollectionReAddOrphanRemoval(): void
    {
        $post             = new DDC1654Post();
        $post->comments[] = new DDC1654Comment();
        $post->comments[] = new DDC1654Comment();

        $this->_em->persist($post);
        $this->_em->flush();

        $comment = $post->comments[0];
        $post->comments->clear();
        $post->comments->add($comment);

        $this->_em->flush();
        $this->_em->clear();

        $comments = $this->_em->getRepository(DDC1654Comment::class)->findAll();
        self::assertCount(1, $comments);
    }
}

/** @Entity */
class DDC1654Post
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @psalm-var Collection<int, DDC1654Comment>
     * @ManyToMany(targetEntity="DDC1654Comment", orphanRemoval=true,
     * cascade={"persist"})
     */
    public $comments = [];
}

/** @Entity */
class DDC1654Comment
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;
}
