<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Description of ExtraLazyCollectionTest
 *
 * @author beberlei
 */
class ExtraLazyCollectionTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $userId;
    private $groupId;
    private $articleId;

    public function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();

        $class = $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $class->associationMappings['groups']['fetch'] = ClassMetadataInfo::FETCH_EXTRA_LAZY;
        $class->associationMappings['articles']['fetch'] = ClassMetadataInfo::FETCH_EXTRA_LAZY;

        $class = $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsGroup');
        $class->associationMappings['users']['fetch'] = ClassMetadataInfo::FETCH_EXTRA_LAZY;


        $this->loadFixture();
    }

    public function tearDown()
    {
        parent::tearDown();

        $class = $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $class->associationMappings['groups']['fetch'] = ClassMetadataInfo::FETCH_LAZY;
        $class->associationMappings['articles']['fetch'] = ClassMetadataInfo::FETCH_LAZY;

        $class = $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsGroup');
        $class->associationMappings['users']['fetch'] = ClassMetadataInfo::FETCH_LAZY;
    }

    /**
     * @group DDC-546
     */
    public function testCountNotInitializesCollection()
    {
        $user = $this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userId);
        $queryCount = $this->getCurrentQueryCount();

        $this->assertFalse($user->groups->isInitialized());
        $this->assertEquals(3, count($user->groups));
        $this->assertFalse($user->groups->isInitialized());

        foreach ($user->groups AS $group) { }

        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount(), "Expecting two queries to be fired for count, then iteration.");
    }

    /**
     * @group DDC-546
     */
    public function testCountWhenNewEntitysPresent()
    {
        $user = $this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userId);

        $newGroup = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $newGroup->name = "Test4";

        $user->addGroup($newGroup);
        $this->_em->persist($newGroup);

        $this->assertFalse($user->groups->isInitialized());
        $this->assertEquals(4, count($user->groups));
        $this->assertFalse($user->groups->isInitialized());
    }

    /**
     * @group DDC-546
     */
    public function testCountWhenInitialized()
    {
        $user = $this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userId);
        $queryCount = $this->getCurrentQueryCount();

        foreach ($user->groups AS $group) { }

        $this->assertTrue($user->groups->isInitialized());
        $this->assertEquals(3, count($user->groups));
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount(), "Should only execute one query to initialize colleciton, no extra query for count() more.");
    }

    /**
     * @group DDC-546
     */
    public function testCountInverseCollection()
    {
        $group = $this->_em->find('Doctrine\Tests\Models\CMS\CmsGroup', $this->groupId);
        $this->assertFalse($group->users->isInitialized(), "Pre-Condition");

        $this->assertEquals(4, count($group->users));
        $this->assertFalse($group->users->isInitialized(), "Extra Lazy collection should not be initialized by counting the collection.");
    }

    /**
     * @group DDC-546
     */
    public function testCountOneToMany()
    {
        $user = $this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userId);
        $this->assertFalse($user->groups->isInitialized(), "Pre-Condition");

        $this->assertEquals(2, count($user->articles));
    }

    /**
     * @group DDC-546
     */
    public function testFullSlice()
    {
        $user = $this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userId);
        $this->assertFalse($user->groups->isInitialized(), "Pre-Condition: Collection is not initialized.");

        $someGroups = $user->groups->slice(null);
        $this->assertEquals(3, count($someGroups));
    }

    /**
     * @group DDC-546
     */
    public function testSlice()
    {
        $user = $this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userId);
        $this->assertFalse($user->groups->isInitialized(), "Pre-Condition: Collection is not initialized.");

        $queryCount = $this->getCurrentQueryCount();

        $someGroups = $user->groups->slice(0, 2);

        $this->assertContainsOnly('Doctrine\Tests\Models\CMS\CmsGroup', $someGroups);
        $this->assertEquals(2, count($someGroups));
        $this->assertFalse($user->groups->isInitialized(), "Slice should not initialize the collection if it wasn't before!");

        $otherGroup = $user->groups->slice(2, 1);

        $this->assertContainsOnly('Doctrine\Tests\Models\CMS\CmsGroup', $otherGroup);
        $this->assertEquals(1, count($otherGroup));
        $this->assertFalse($user->groups->isInitialized());

        foreach ($user->groups AS $group) { }

        $this->assertTrue($user->groups->isInitialized());
        $this->assertEquals(3, count($user->groups));

        $this->assertEquals($queryCount + 3, $this->getCurrentQueryCount());
    }

    /**
     * @group DDC-546
     */
    public function testSliceInitializedCollection()
    {
        $user = $this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userId);
        $queryCount = $this->getCurrentQueryCount();

        foreach ($user->groups AS $group) { }

        $someGroups = $user->groups->slice(0, 2);

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $this->assertEquals(2, count($someGroups));
        $this->assertTrue($user->groups->contains($someGroups[0]));
        $this->assertTrue($user->groups->contains($someGroups[1]));
    }

    /**
     * @group DDC-546
     */
    public function testSliceInverseCollection()
    {
        $group = $this->_em->find('Doctrine\Tests\Models\CMS\CmsGroup', $this->groupId);
        $this->assertFalse($group->users->isInitialized(), "Pre-Condition");
        $queryCount = $this->getCurrentQueryCount();

        $someUsers = $group->users->slice(0, 2);
        $otherUsers = $group->users->slice(2, 2);

        $this->assertContainsOnly('Doctrine\Tests\Models\CMS\CmsUser', $someUsers);
        $this->assertContainsOnly('Doctrine\Tests\Models\CMS\CmsUser', $otherUsers);
        $this->assertEquals(2, count($someUsers));
        $this->assertEquals(2, count($otherUsers));

        // +2 queries executed by slice
        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount(), "Slicing two parts should only execute two additional queries.");
    }

    /**
     * @group DDC-546
     */
    public function testSliceOneToMany()
    {
        $user = $this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userId);
        $this->assertFalse($user->articles->isInitialized(), "Pre-Condition: Collection is not initialized.");

        $queryCount = $this->getCurrentQueryCount();

        $someArticle = $user->articles->slice(0, 1);
        $otherArticle = $user->articles->slice(1, 1);

        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount());
    }

    /**
     * @group DDC-546
     */
    public function testContainsOneToMany()
    {
        $user = $this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userId);
        $this->assertFalse($user->articles->isInitialized(), "Pre-Condition: Collection is not initialized.");

        $article = $this->_em->find('Doctrine\Tests\Models\CMS\CmsArticle', $this->articleId);

        $queryCount = $this->getCurrentQueryCount();
        $this->assertTrue($user->articles->contains($article));
        $this->assertFalse($user->articles->isInitialized(), "Post-Condition: Collection is not initialized.");
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $article = new \Doctrine\Tests\Models\CMS\CmsArticle();
        $article->topic = "Testnew";
        $article->text = "blub";

        $queryCount = $this->getCurrentQueryCount();
        $this->assertFalse($user->articles->contains($article));
        $this->assertEquals($queryCount, $this->getCurrentQueryCount(), "Checking for contains of new entity should cause no query to be executed.");

        $this->_em->persist($article);
        $this->_em->flush();

        $queryCount = $this->getCurrentQueryCount();
        $this->assertFalse($user->articles->contains($article));
        $this->assertEquals($queryCount+1, $this->getCurrentQueryCount(), "Checking for contains of managed entity should cause one query to be executed.");
        $this->assertFalse($user->articles->isInitialized(), "Post-Condition: Collection is not initialized.");
    }

    /**
     * @group DDC-546
     */
    public function testContainsManyToMany()
    {
        $user = $this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userId);
        $this->assertFalse($user->groups->isInitialized(), "Pre-Condition: Collection is not initialized.");

        $group = $this->_em->find('Doctrine\Tests\Models\CMS\CmsGroup', $this->groupId);

        $queryCount = $this->getCurrentQueryCount();
        $this->assertTrue($user->groups->contains($group));
        $this->assertEquals($queryCount+1, $this->getCurrentQueryCount(), "Checking for contains of managed entity should cause one query to be executed.");
        $this->assertFalse($user->groups->isInitialized(), "Post-Condition: Collection is not initialized.");

        $group = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $group->name = "A New group!";

        $queryCount = $this->getCurrentQueryCount();
        $this->assertFalse($user->groups->contains($group));
        $this->assertEquals($queryCount, $this->getCurrentQueryCount(), "Checking for contains of new entity should cause no query to be executed.");
        $this->assertFalse($user->groups->isInitialized(), "Post-Condition: Collection is not initialized.");

        $this->_em->persist($group);
        $this->_em->flush();

        $queryCount = $this->getCurrentQueryCount();
        $this->assertFalse($user->groups->contains($group));
        $this->assertEquals($queryCount+1, $this->getCurrentQueryCount(), "Checking for contains of managed entity should cause one query to be executed.");
        $this->assertFalse($user->groups->isInitialized(), "Post-Condition: Collection is not initialized.");
    }

    /**
     * @group DDC-546
     */
    public function testContainsManyToManyInverse()
    {
        $group = $this->_em->find('Doctrine\Tests\Models\CMS\CmsGroup', $this->groupId);
        $this->assertFalse($group->users->isInitialized(), "Pre-Condition: Collection is not initialized.");

        $user = $this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userId);

        $queryCount = $this->getCurrentQueryCount();
        $this->assertTrue($group->users->contains($user));
        $this->assertEquals($queryCount+1, $this->getCurrentQueryCount(), "Checking for contains of managed entity should cause one query to be executed.");
        $this->assertFalse($user->groups->isInitialized(), "Post-Condition: Collection is not initialized.");

        $newUser = new \Doctrine\Tests\Models\CMS\CmsUser();
        $newUser->name = "A New group!";

        $queryCount = $this->getCurrentQueryCount();
        $this->assertFalse($group->users->contains($newUser));
        $this->assertEquals($queryCount, $this->getCurrentQueryCount(), "Checking for contains of new entity should cause no query to be executed.");
        $this->assertFalse($user->groups->isInitialized(), "Post-Condition: Collection is not initialized.");
    }

    /**
     * @group DDC-1399
     */
    public function testCountAfterAddThenFlush()
    {
        $user = $this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userId);

        $newGroup = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $newGroup->name = "Test4";

        $user->addGroup($newGroup);
        $this->_em->persist($newGroup);

        $this->assertFalse($user->groups->isInitialized());
        $this->assertEquals(4, count($user->groups));
        $this->assertFalse($user->groups->isInitialized());

        $this->_em->flush();

        $this->assertEquals(4, count($user->groups));
    }

    /**
     * @group DDC-1462
     */
    public function testSliceOnDirtyCollection()
    {
        $user = $this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userId);
        /* @var $user CmsUser */

        $newGroup = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $newGroup->name = "Test4";

        $user->addGroup($newGroup);
        $this->_em->persist($newGroup);

        $qc = $this->getCurrentQueryCount();
        $groups = $user->groups->slice(0, 10);

        $this->assertEquals(4, count($groups));
        $this->assertEquals($qc + 1, $this->getCurrentQueryCount());
    }

    private function loadFixture()
    {
        $user1 = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user1->username = "beberlei";
        $user1->name = "Benjamin";
        $user1->status = "active";

        $user2 = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user2->username = "jwage";
        $user2->name = "Jonathan";
        $user2->status = "active";

        $user3 = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user3->username = "romanb";
        $user3->name = "Roman";
        $user3->status = "active";

        $user4 = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user4->username = "gblanco";
        $user4->name = "Guilherme";
        $user4->status = "active";

        $this->_em->persist($user1);
        $this->_em->persist($user2);
        $this->_em->persist($user3);
        $this->_em->persist($user4);

        $group1 = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $group1->name = "Test1";

        $group2 = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $group2->name = "Test2";

        $group3 = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $group3->name = "Test3";

        $user1->addGroup($group1);
        $user1->addGroup($group2);
        $user1->addGroup($group3);

        $user2->addGroup($group1);
        $user3->addGroup($group1);
        $user4->addGroup($group1);

        $this->_em->persist($group1);
        $this->_em->persist($group2);
        $this->_em->persist($group3);

        $article1 = new \Doctrine\Tests\Models\CMS\CmsArticle();
        $article1->topic = "Test";
        $article1->text = "Test";
        $article1->setAuthor($user1);

        $article2 = new \Doctrine\Tests\Models\CMS\CmsArticle();
        $article2->topic = "Test";
        $article2->text = "Test";
        $article2->setAuthor($user1);

        $this->_em->persist($article1);
        $this->_em->persist($article2);

        $this->_em->flush();
        $this->_em->clear();

        $this->articleId = $article1->id;
        $this->userId = $user1->getId();
        $this->groupId = $group1->id;
    }
}