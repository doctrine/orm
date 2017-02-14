<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

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
        $this->em->getMetadataFactory()->setCacheDriver(new FilesystemCache($testDir));

        try {
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(DDC742User::class),
                    $this->em->getClassMetadata(DDC742Comment::class)
                ]
            );
        } catch(\Exception $e) {
        }

        // make sure classes will be deserialized from caches
        $this->em->getMetadataFactory()->setMetadataFor(DDC742User::class, null);
        $this->em->getMetadataFactory()->setMetadataFor(DDC742Comment::class, null);
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

        $this->em->persist($user);
        $this->em->persist($comment1);
        $this->em->persist($comment2);
        $this->em->persist($comment3);
        $this->em->flush();
        $this->em->clear();

        $user = $this->em->find(DDC742User::class, $user->id);
        $user->favoriteComments->add($this->em->find(DDC742Comment::class, $comment3->id));

        $this->em->flush();

        $this->addToAssertionCount(1);
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="ddc742_users")
 */
class DDC742User
{
    /**
     * User Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    public $id;

    /**
     * @ORM\Column(length=100, type="string")
     * @var string
     */
    public $title;

    /**
     * @ORM\ManyToMany(targetEntity="DDC742Comment", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinTable(
     *  name="user_comments",
     *  joinColumns={@ORM\JoinColumn(name="user_id",referencedColumnName="id")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="comment_id", referencedColumnName="id")}
     * )
     *
     * @var \Doctrine\ORM\PersistentCollection
     */
    public $favoriteComments;
}

/**
 * @ORM\Entity
 * @ORM\Table(name="ddc742_comments")
 */
class DDC742Comment
{
    /**
     * User Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    public $id;

    /**
     * @ORM\Column(length=100, type="string")
     * @var string
     */
    public $content;
}
