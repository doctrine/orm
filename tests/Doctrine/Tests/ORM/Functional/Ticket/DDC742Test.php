<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @group non-cacheable
 */
class DDC742Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $testDir = sys_get_temp_dir() . '/DDC742Test' . uniqid();

        mkdir($testDir);

        // using a Filesystemcache to ensure that the cached data is serialized
        $this->_em->getMetadataFactory()->setCacheDriver(new FilesystemCache($testDir));

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC742User'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC742Comment')
            ));
        } catch(\Exception $e) {
        }

        // make sure classes will be deserialized from caches
        $this->_em->getMetadataFactory()->setMetadataFor(__NAMESPACE__ . '\DDC742User', null);
        $this->_em->getMetadataFactory()->setMetadataFor(__NAMESPACE__ . '\DDC742Comment', null);
    }

    public function testIssue()
    {
        $user = new DDC742User();
        $user->title = "Foo";
        $user->favoriteComments = new ArrayCollection();

        $comment1 = new DDC742Comment();
        $comment1->content = "foo";

        $comment2 = new DDC742Comment();
        $comment2->content = "bar";

        $comment3 = new DDC742Comment();
        $comment3->content = "baz";

        $user->favoriteComments->add($comment1);
        $user->favoriteComments->add($comment2);

        $this->_em->persist($user);
        $this->_em->persist($comment1);
        $this->_em->persist($comment2);
        $this->_em->persist($comment3);
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find(get_class($user), $user->id);
        $comment3 = $this->_em->find(get_class($comment3), $comment3->id);
        $user->favoriteComments->add($comment3);
        $this->_em->flush();
    }
}

/**
 * @Entity
 * @Table(name="ddc742_users")
 */
class DDC742User
{
    /**
     * User Id
     *
     * @Id
     * @GeneratedValue(strategy="AUTO")
     * @Column(type="integer")
     * @var int
     */
    public $id;
    /**
     * @Column(length=100, type="string")
     * @var string
     */
    public $title;
    /**
     * @ManyToMany(targetEntity="DDC742Comment", cascade={"persist"}, fetch="EAGER")
     * @JoinTable(
     *  name="user_comments",
     *  joinColumns={@JoinColumn(name="user_id",referencedColumnName="id")},
     *  inverseJoinColumns={@JoinColumn(name="comment_id", referencedColumnName="id")}
     * )
     *
     * @var Doctrine\ORM\PersistentCollection
     */
    public $favoriteComments;
}

/**
 * @Entity
 * @Table(name="ddc742_comments")
 */
class DDC742Comment
{
    /**
     * User Id
     *
     * @Id
     * @GeneratedValue(strategy="AUTO")
     * @Column(type="integer")
     * @var int
     */
    public $id;
    /**
     * @Column(length=100, type="string")
     * @var string
     */
    public $content;
}
