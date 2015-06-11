<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * @group DDC-1654
 */
class DDC1654Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->setUpEntitySchema(array(
            __NAMESPACE__ . '\\DDC1654Post',
            __NAMESPACE__ . '\\DDC1654Comment',
        ));
    }

    public function tearDown()
    {
        $conn = static::$_sharedConn;
        $conn->executeUpdate('DELETE FROM ddc1654post_ddc1654comment');
        $conn->executeUpdate('DELETE FROM DDC1654Comment');
        $conn->executeUpdate('DELETE FROM DDC1654Post');
    }

    public function testManyToManyRemoveFromCollectionOrphanRemoval()
    {
        $post = new DDC1654Post();
        $post->comments[] = new DDC1654Comment();
        $post->comments[] = new DDC1654Comment();

        $this->_em->persist($post);
        $this->_em->flush();

        $post->comments->remove(0);
        $post->comments->remove(1);

        $this->_em->flush();
        $this->_em->clear();

        $comments = $this->_em->getRepository(__NAMESPACE__ . '\\DDC1654Comment')->findAll();
        $this->assertEquals(0, count($comments));
    }

    public function testManyToManyRemoveElementFromCollectionOrphanRemoval()
    {
        $post = new DDC1654Post();
        $post->comments[] = new DDC1654Comment();
        $post->comments[] = new DDC1654Comment();

        $this->_em->persist($post);
        $this->_em->flush();

        $post->comments->removeElement($post->comments[0]);
        $post->comments->removeElement($post->comments[1]);

        $this->_em->flush();
        $this->_em->clear();

        $comments = $this->_em->getRepository(__NAMESPACE__ . '\\DDC1654Comment')->findAll();
        $this->assertEquals(0, count($comments));
    }

    /**
     * @group DDC-3382
     */
    public function testManyToManyRemoveElementFromReAddToCollectionOrphanRemoval()
    {
        $post = new DDC1654Post();
        $post->comments[] = new DDC1654Comment();
        $post->comments[] = new DDC1654Comment();

        $this->_em->persist($post);
        $this->_em->flush();

        $comment = $post->comments[0];
        $post->comments->removeElement($comment);
        $post->comments->add($comment);

        $this->_em->flush();
        $this->_em->clear();

        $comments = $this->_em->getRepository(__NAMESPACE__ . '\\DDC1654Comment')->findAll();
        $this->assertEquals(2, count($comments));
    }

    public function testManyToManyClearCollectionOrphanRemoval()
    {
        $post = new DDC1654Post();
        $post->comments[] = new DDC1654Comment();
        $post->comments[] = new DDC1654Comment();

        $this->_em->persist($post);
        $this->_em->flush();

        $post->comments->clear();

        $this->_em->flush();
        $this->_em->clear();

        $comments = $this->_em->getRepository(__NAMESPACE__ . '\\DDC1654Comment')->findAll();
        $this->assertEquals(0, count($comments));

    }

    /**
     * @group DDC-3382
     */
    public function testManyToManyClearCollectionReAddOrphanRemoval()
    {
        $post = new DDC1654Post();
        $post->comments[] = new DDC1654Comment();
        $post->comments[] = new DDC1654Comment();

        $this->_em->persist($post);
        $this->_em->flush();

        $comment = $post->comments[0];
        $post->comments->clear();
        $post->comments->add($comment);

        $this->_em->flush();
        $this->_em->clear();

        $comments = $this->_em->getRepository(__NAMESPACE__ . '\\DDC1654Comment')->findAll();
        $this->assertEquals(1, count($comments));
    }
}

/**
 * @Entity
 */
class DDC1654Post
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     */
    public $id;

    /**
     * @ManyToMany(targetEntity="DDC1654Comment", orphanRemoval=true,
     * cascade={"persist"})
     */
    public $comments = array();
}

/**
 * @Entity
 */
class DDC1654Comment
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     */
    public $id;
}
