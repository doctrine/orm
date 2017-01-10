<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

class DDC832Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        $platform = $this->em->getConnection()->getDatabasePlatform();

        if ($platform->getName() === 'oracle') {
            $this->markTestSkipped('Doesnt run on Oracle.');
        }

        $this->em->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());

        try {
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(DDC832JoinedIndex::class),
                    $this->em->getClassMetadata(DDC832JoinedTreeIndex::class),
                    $this->em->getClassMetadata(DDC832Like::class),
                ]
            );
        } catch(\Exception $e) {
        }
    }

    public function tearDown()
    {
        /* @var $sm \Doctrine\DBAL\Schema\AbstractSchemaManager */
        $platform = $this->em->getConnection()->getDatabasePlatform();

        $sm = $this->em->getConnection()->getSchemaManager();

        $sm->dropTable($platform->quoteIdentifier('TREE_INDEX'));
        $sm->dropTable($platform->quoteIdentifier('INDEX'));
        $sm->dropTable($platform->quoteIdentifier('LIKE'));
    }

    /**
     * @group DDC-832
     */
    public function testQuotedTableBasicUpdate()
    {
        $like = new DDC832Like("test");
        $this->em->persist($like);
        $this->em->flush();

        $like->word = "test2";
        $this->em->flush();
        $this->em->clear();

        self::assertEquals($like, $this->em->find(DDC832Like::class, $like->id));
    }

    /**
     * @group DDC-832
     */
    public function testQuotedTableBasicRemove()
    {
        $like = new DDC832Like("test");
        $this->em->persist($like);
        $this->em->flush();

        $idToBeRemoved = $like->id;

        $this->em->remove($like);
        $this->em->flush();
        $this->em->clear();

        self::assertNull($this->em->find(DDC832Like::class, $idToBeRemoved));
    }

    /**
     * @group DDC-832
     */
    public function testQuotedTableJoinedUpdate()
    {
        $index = new DDC832JoinedIndex("test");
        $this->em->persist($index);
        $this->em->flush();

        $index->name = "asdf";
        $this->em->flush();
        $this->em->clear();

        self::assertEquals($index, $this->em->find(DDC832JoinedIndex::class, $index->id));
    }

    /**
     * @group DDC-832
     */
    public function testQuotedTableJoinedRemove()
    {
        $index = new DDC832JoinedIndex("test");
        $this->em->persist($index);
        $this->em->flush();

        $idToBeRemoved = $index->id;

        $this->em->remove($index);
        $this->em->flush();
        $this->em->clear();

        self::assertNull($this->em->find(DDC832JoinedIndex::class, $idToBeRemoved));
    }

    /**
     * @group DDC-832
     */
    public function testQuotedTableJoinedChildUpdate()
    {
        $index = new DDC832JoinedTreeIndex("test", 1, 2);
        $this->em->persist($index);
        $this->em->flush();

        $index->name = "asdf";
        $this->em->flush();
        $this->em->clear();

        self::assertEquals($index, $this->em->find(DDC832JoinedTreeIndex::class, $index->id));
    }

    /**
     * @group DDC-832
     */
    public function testQuotedTableJoinedChildRemove()
    {
        $index = new DDC832JoinedTreeIndex("test", 1, 2);
        $this->em->persist($index);
        $this->em->flush();

        $idToBeRemoved = $index->id;

        $this->em->remove($index);
        $this->em->flush();
        $this->em->clear();

        self::assertNull($this->em->find(DDC832JoinedTreeIndex::class, $idToBeRemoved));
    }
}

/**
 * @Entity
 * @Table(name="LIKE")
 */
class DDC832Like
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     */
    public $id;

    /** @Column(type="string") */
    public $word;

    /**
     * @version
     * @Column(type="integer")
     */
    public $version;

    public function __construct($word)
    {
        $this->word = $word;
    }
}

/**
 * @Entity
 * @Table(name="INDEX")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"like" = "DDC832JoinedIndex", "fuzzy" = "DDC832JoinedTreeIndex"})
 */
class DDC832JoinedIndex
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     */
    public $id;

    /** @Column(type="string") */
    public $name;

    /**
     * @version
     * @Column(type="integer")
     */
    public $version;

    public function __construct($name)
    {
        $this->name = $name;
    }
}

/**
 * @Entity
 * @Table(name="TREE_INDEX")
 */
class DDC832JoinedTreeIndex extends DDC832JoinedIndex
{
    /** @Column(type="integer") */
    public $lft;

    /** @Column(type="integer") */
    public $rgt;

    public function __construct($name, $lft, $rgt)
    {
        $this->name = $name;
        $this->lft = $lft;
        $this->rgt = $rgt;
    }
}
