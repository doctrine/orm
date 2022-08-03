<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\DDC2504\DDC2504ChildClass;
use Doctrine\Tests\Models\DDC2504\DDC2504OtherClass;
use Doctrine\Tests\OrmFunctionalTestCase;

use function array_shift;
use function assert;
use function count;

/**
 * Description of ExtraLazyCollectionTest
 */
class ExtraLazyCollectionTest extends OrmFunctionalTestCase
{
    /** @var int */
    private $userId;

    /** @var int */
    private $userId2;

    /** @var int */
    private $groupId;

    /** @var int */
    private $articleId;

    /** @var int */
    private $ddc2504OtherClassId;

    /** @var int */
    private $ddc2504ChildClassId;

    /** @var string */
    private $username;

    /** @var string */
    private $groupname;

    /** @var string */
    private $topic;

    /** @var CmsPhonenumber */
    private $phonenumber;

    protected function setUp(): void
    {
        $this->useModelSet('tweet');
        $this->useModelSet('cms');
        $this->useModelSet('ddc2504');
        parent::setUp();

        $class                                                 = $this->_em->getClassMetadata(CmsUser::class);
        $class->associationMappings['groups']['fetch']         = ClassMetadataInfo::FETCH_EXTRA_LAZY;
        $class->associationMappings['groups']['indexBy']       = 'name';
        $class->associationMappings['articles']['fetch']       = ClassMetadataInfo::FETCH_EXTRA_LAZY;
        $class->associationMappings['articles']['indexBy']     = 'topic';
        $class->associationMappings['phonenumbers']['fetch']   = ClassMetadataInfo::FETCH_EXTRA_LAZY;
        $class->associationMappings['phonenumbers']['indexBy'] = 'phonenumber';

        foreach (['phonenumbers', 'articles', 'users'] as $field) {
            if (isset($class->associationMappings[$field]['cache'])) {
                $this->previousCacheConfig[$field] = $class->associationMappings[$field]['cache'];
            }

            unset($class->associationMappings[$field]['cache']);
        }

        $class                                          = $this->_em->getClassMetadata(CmsGroup::class);
        $class->associationMappings['users']['fetch']   = ClassMetadataInfo::FETCH_EXTRA_LAZY;
        $class->associationMappings['users']['indexBy'] = 'username';

        $this->loadFixture();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $class                                               = $this->_em->getClassMetadata(CmsUser::class);
        $class->associationMappings['groups']['fetch']       = ClassMetadataInfo::FETCH_LAZY;
        $class->associationMappings['articles']['fetch']     = ClassMetadataInfo::FETCH_LAZY;
        $class->associationMappings['phonenumbers']['fetch'] = ClassMetadataInfo::FETCH_LAZY;

        foreach (['phonenumbers', 'articles', 'users'] as $field) {
            if (isset($this->previousCacheConfig[$field])) {
                $class->associationMappings[$field]['cache'] = $this->previousCacheConfig[$field];
                unset($this->previousCacheConfig[$field]);
            }
        }

        unset($class->associationMappings['groups']['indexBy']);
        unset($class->associationMappings['articles']['indexBy']);
        unset($class->associationMappings['phonenumbers']['indexBy']);

        $class                                        = $this->_em->getClassMetadata(CmsGroup::class);
        $class->associationMappings['users']['fetch'] = ClassMetadataInfo::FETCH_LAZY;

        unset($class->associationMappings['users']['indexBy']);
    }

    /**
     * @group DDC-546
     * @group non-cacheable
     */
    public function testCountNotInitializesCollection(): void
    {
        $user       = $this->_em->find(CmsUser::class, $this->userId);
        $queryCount = $this->getCurrentQueryCount();

        $this->assertFalse($user->groups->isInitialized());
        $this->assertEquals(3, count($user->groups));
        $this->assertFalse($user->groups->isInitialized());

        foreach ($user->groups as $group) {
        }

        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount(), 'Expecting two queries to be fired for count, then iteration.');
    }

    /**
     * @group DDC-546
     */
    public function testCountWhenNewEntityPresent(): void
    {
        $user = $this->_em->find(CmsUser::class, $this->userId);

        $newGroup       = new CmsGroup();
        $newGroup->name = 'Test4';

        $user->addGroup($newGroup);
        $this->_em->persist($newGroup);

        $this->assertFalse($user->groups->isInitialized());
        $this->assertEquals(4, count($user->groups));
        $this->assertFalse($user->groups->isInitialized());
    }

    /**
     * @group DDC-546
     * @group non-cacheable
     */
    public function testCountWhenInitialized(): void
    {
        $user       = $this->_em->find(CmsUser::class, $this->userId);
        $queryCount = $this->getCurrentQueryCount();

        foreach ($user->groups as $group) {
        }

        $this->assertTrue($user->groups->isInitialized());
        $this->assertEquals(3, count($user->groups));
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount(), 'Should only execute one query to initialize collection, no extra query for count() more.');
    }

    /**
     * @group DDC-546
     */
    public function testCountInverseCollection(): void
    {
        $group = $this->_em->find(CmsGroup::class, $this->groupId);
        $this->assertFalse($group->users->isInitialized(), 'Pre-Condition');

        $this->assertEquals(4, count($group->users));
        $this->assertFalse($group->users->isInitialized(), 'Extra Lazy collection should not be initialized by counting the collection.');
    }

    /**
     * @group DDC-546
     */
    public function testCountOneToMany(): void
    {
        $user = $this->_em->find(CmsUser::class, $this->userId);
        $this->assertFalse($user->groups->isInitialized(), 'Pre-Condition');

        $this->assertEquals(2, count($user->articles));
    }

    /**
     * @group DDC-2504
     */
    public function testCountOneToManyJoinedInheritance(): void
    {
        $otherClass = $this->_em->find(DDC2504OtherClass::class, $this->ddc2504OtherClassId);

        $this->assertFalse($otherClass->childClasses->isInitialized(), 'Pre-Condition');
        $this->assertEquals(2, count($otherClass->childClasses));
    }

    /**
     * @group DDC-546
     */
    public function testFullSlice(): void
    {
        $user = $this->_em->find(CmsUser::class, $this->userId);
        $this->assertFalse($user->groups->isInitialized(), 'Pre-Condition: Collection is not initialized.');

        $someGroups = $user->groups->slice(null);
        $this->assertEquals(3, count($someGroups));
    }

    /**
     * @group DDC-546
     * @group non-cacheable
     */
    public function testSlice(): void
    {
        $user = $this->_em->find(CmsUser::class, $this->userId);
        $this->assertFalse($user->groups->isInitialized(), 'Pre-Condition: Collection is not initialized.');

        $queryCount = $this->getCurrentQueryCount();

        $someGroups = $user->groups->slice(0, 2);

        $this->assertContainsOnly(CmsGroup::class, $someGroups);
        $this->assertEquals(2, count($someGroups));
        $this->assertFalse($user->groups->isInitialized(), "Slice should not initialize the collection if it wasn't before!");

        $otherGroup = $user->groups->slice(2, 1);

        $this->assertContainsOnly(CmsGroup::class, $otherGroup);
        $this->assertEquals(1, count($otherGroup));
        $this->assertFalse($user->groups->isInitialized());

        foreach ($user->groups as $group) {
        }

        $this->assertTrue($user->groups->isInitialized());
        $this->assertEquals(3, count($user->groups));

        $this->assertEquals($queryCount + 3, $this->getCurrentQueryCount());
    }

    /**
     * @group DDC-546
     * @group non-cacheable
     */
    public function testSliceInitializedCollection(): void
    {
        $user       = $this->_em->find(CmsUser::class, $this->userId);
        $queryCount = $this->getCurrentQueryCount();

        foreach ($user->groups as $group) {
        }

        $someGroups = $user->groups->slice(0, 2);

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $this->assertEquals(2, count($someGroups));
        $this->assertTrue($user->groups->contains(array_shift($someGroups)));
        $this->assertTrue($user->groups->contains(array_shift($someGroups)));
    }

    /**
     * @group DDC-546
     */
    public function testSliceInverseCollection(): void
    {
        $group = $this->_em->find(CmsGroup::class, $this->groupId);
        $this->assertFalse($group->users->isInitialized(), 'Pre-Condition');
        $queryCount = $this->getCurrentQueryCount();

        $someUsers  = $group->users->slice(0, 2);
        $otherUsers = $group->users->slice(2, 2);

        $this->assertContainsOnly(CmsUser::class, $someUsers);
        $this->assertContainsOnly(CmsUser::class, $otherUsers);
        $this->assertEquals(2, count($someUsers));
        $this->assertEquals(2, count($otherUsers));

        // +2 queries executed by slice
        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount(), 'Slicing two parts should only execute two additional queries.');
    }

    /**
     * @group DDC-546
     */
    public function testSliceOneToMany(): void
    {
        $user = $this->_em->find(CmsUser::class, $this->userId);
        $this->assertFalse($user->articles->isInitialized(), 'Pre-Condition: Collection is not initialized.');

        $queryCount = $this->getCurrentQueryCount();

        $someArticle  = $user->articles->slice(0, 1);
        $otherArticle = $user->articles->slice(1, 1);

        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount());
    }

    /**
     * @group DDC-546
     */
    public function testContainsOneToMany(): void
    {
        $user = $this->_em->find(CmsUser::class, $this->userId);
        $this->assertFalse($user->articles->isInitialized(), 'Pre-Condition: Collection is not initialized.');

        // Test One to Many existence retrieved from DB
        $article    = $this->_em->find(CmsArticle::class, $this->articleId);
        $queryCount = $this->getCurrentQueryCount();

        $this->assertTrue($user->articles->contains($article));
        $this->assertFalse($user->articles->isInitialized(), 'Post-Condition: Collection is not initialized.');
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        // Test One to Many existence with state new
        $article        = new CmsArticle();
        $article->topic = 'Testnew';
        $article->text  = 'blub';

        $queryCount = $this->getCurrentQueryCount();
        $this->assertFalse($user->articles->contains($article));
        $this->assertEquals($queryCount, $this->getCurrentQueryCount(), 'Checking for contains of new entity should cause no query to be executed.');

        // Test One to Many existence with state clear
        $this->_em->persist($article);
        $this->_em->flush();

        $queryCount = $this->getCurrentQueryCount();
        $this->assertFalse($user->articles->contains($article));
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount(), 'Checking for contains of persisted entity should cause one query to be executed.');
        $this->assertFalse($user->articles->isInitialized(), 'Post-Condition: Collection is not initialized.');

        // Test One to Many existence with state managed
        $article        = new CmsArticle();
        $article->topic = 'How to not fail anymore on tests';
        $article->text  = 'That is simple! Just write more tests!';

        $this->_em->persist($article);

        $queryCount = $this->getCurrentQueryCount();

        $this->assertFalse($user->articles->contains($article));
        $this->assertEquals($queryCount, $this->getCurrentQueryCount(), 'Checking for contains of managed entity (but not persisted) should cause no query to be executed.');
        $this->assertFalse($user->articles->isInitialized(), 'Post-Condition: Collection is not initialized.');
    }

    /**
     * @group DDC-2504
     */
    public function testLazyOneToManyJoinedInheritanceIsLazilyInitialized(): void
    {
        $otherClass = $this->_em->find(DDC2504OtherClass::class, $this->ddc2504OtherClassId);

        $this->assertFalse($otherClass->childClasses->isInitialized(), 'Collection is not initialized.');
    }

    /**
     * @group DDC-2504
     */
    public function testContainsOnOneToManyJoinedInheritanceWillNotInitializeCollectionWhenMatchingItemIsFound(): void
    {
        $otherClass = $this->_em->find(DDC2504OtherClass::class, $this->ddc2504OtherClassId);

        // Test One to Many existence retrieved from DB
        $childClass = $this->_em->find(DDC2504ChildClass::class, $this->ddc2504ChildClassId);
        $queryCount = $this->getCurrentQueryCount();

        $this->assertTrue($otherClass->childClasses->contains($childClass));
        $this->assertFalse($otherClass->childClasses->isInitialized(), 'Collection is not initialized.');
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount(), 'Search operation was performed via SQL');
    }

    /**
     * @group DDC-2504
     */
    public function testContainsOnOneToManyJoinedInheritanceWillNotCauseQueriesWhenNonPersistentItemIsMatched(): void
    {
        $otherClass = $this->_em->find(DDC2504OtherClass::class, $this->ddc2504OtherClassId);
        $queryCount = $this->getCurrentQueryCount();

        $this->assertFalse($otherClass->childClasses->contains(new DDC2504ChildClass()));
        $this->assertEquals(
            $queryCount,
            $this->getCurrentQueryCount(),
            'Checking for contains of new entity should cause no query to be executed.'
        );
    }

    /**
     * @group DDC-2504
     */
    public function testContainsOnOneToManyJoinedInheritanceWillNotInitializeCollectionWithClearStateMatchingItem(): void
    {
        $otherClass = $this->_em->find(DDC2504OtherClass::class, $this->ddc2504OtherClassId);
        $childClass = new DDC2504ChildClass();

        // Test One to Many existence with state clear
        $this->_em->persist($childClass);
        $this->_em->flush();

        $queryCount = $this->getCurrentQueryCount();
        $this->assertFalse($otherClass->childClasses->contains($childClass));
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount(), 'Checking for contains of persisted entity should cause one query to be executed.');
        $this->assertFalse($otherClass->childClasses->isInitialized(), 'Post-Condition: Collection is not initialized.');
    }

    /**
     * @group DDC-2504
     */
    public function testContainsOnOneToManyJoinedInheritanceWillNotInitializeCollectionWithNewStateNotMatchingItem(): void
    {
        $otherClass = $this->_em->find(DDC2504OtherClass::class, $this->ddc2504OtherClassId);
        $childClass = new DDC2504ChildClass();

        $this->_em->persist($childClass);

        $queryCount = $this->getCurrentQueryCount();

        $this->assertFalse($otherClass->childClasses->contains($childClass));
        $this->assertEquals($queryCount, $this->getCurrentQueryCount(), 'Checking for contains of managed entity (but not persisted) should cause no query to be executed.');
        $this->assertFalse($otherClass->childClasses->isInitialized(), 'Post-Condition: Collection is not initialized.');
    }

    /**
     * @group DDC-2504
     */
    public function testCountingOnOneToManyJoinedInheritanceWillNotInitializeCollection(): void
    {
        $otherClass = $this->_em->find(DDC2504OtherClass::class, $this->ddc2504OtherClassId);

        $this->assertEquals(2, count($otherClass->childClasses));

        $this->assertFalse($otherClass->childClasses->isInitialized());
    }

    /**
     * @group DDC-546
     */
    public function testContainsManyToMany(): void
    {
        $user = $this->_em->find(CmsUser::class, $this->userId);
        $this->assertFalse($user->groups->isInitialized(), 'Pre-Condition: Collection is not initialized.');

        // Test Many to Many existence retrieved from DB
        $group      = $this->_em->find(CmsGroup::class, $this->groupId);
        $queryCount = $this->getCurrentQueryCount();

        $this->assertTrue($user->groups->contains($group));
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount(), 'Checking for contains of managed entity should cause one query to be executed.');
        $this->assertFalse($user->groups->isInitialized(), 'Post-Condition: Collection is not initialized.');

        // Test Many to Many existence with state new
        $group       = new CmsGroup();
        $group->name = 'A New group!';

        $queryCount = $this->getCurrentQueryCount();

        $this->assertFalse($user->groups->contains($group));
        $this->assertEquals($queryCount, $this->getCurrentQueryCount(), 'Checking for contains of new entity should cause no query to be executed.');
        $this->assertFalse($user->groups->isInitialized(), 'Post-Condition: Collection is not initialized.');

        // Test Many to Many existence with state clear
        $this->_em->persist($group);
        $this->_em->flush();

        $queryCount = $this->getCurrentQueryCount();

        $this->assertFalse($user->groups->contains($group));
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount(), 'Checking for contains of persisted entity should cause one query to be executed.');
        $this->assertFalse($user->groups->isInitialized(), 'Post-Condition: Collection is not initialized.');

        // Test Many to Many existence with state managed
        $group       = new CmsGroup();
        $group->name = 'My managed group';

        $this->_em->persist($group);

        $queryCount = $this->getCurrentQueryCount();

        $this->assertFalse($user->groups->contains($group));
        $this->assertEquals($queryCount, $this->getCurrentQueryCount(), 'Checking for contains of managed entity (but not persisted) should cause no query to be executed.');
        $this->assertFalse($user->groups->isInitialized(), 'Post-Condition: Collection is not initialized.');
    }

    /**
     * @group DDC-546
     */
    public function testContainsManyToManyInverse(): void
    {
        $group = $this->_em->find(CmsGroup::class, $this->groupId);
        $this->assertFalse($group->users->isInitialized(), 'Pre-Condition: Collection is not initialized.');

        $user = $this->_em->find(CmsUser::class, $this->userId);

        $queryCount = $this->getCurrentQueryCount();
        $this->assertTrue($group->users->contains($user));
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount(), 'Checking for contains of managed entity should cause one query to be executed.');
        $this->assertFalse($user->groups->isInitialized(), 'Post-Condition: Collection is not initialized.');

        $newUser       = new CmsUser();
        $newUser->name = 'A New group!';

        $queryCount = $this->getCurrentQueryCount();
        $this->assertFalse($group->users->contains($newUser));
        $this->assertEquals($queryCount, $this->getCurrentQueryCount(), 'Checking for contains of new entity should cause no query to be executed.');
        $this->assertFalse($user->groups->isInitialized(), 'Post-Condition: Collection is not initialized.');
    }

    /**
     * @group DDC-1399
     */
    public function testCountAfterAddThenFlush(): void
    {
        $user = $this->_em->find(CmsUser::class, $this->userId);

        $newGroup       = new CmsGroup();
        $newGroup->name = 'Test4';

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
     * @group non-cacheable
     */
    public function testSliceOnDirtyCollection(): void
    {
        $user = $this->_em->find(CmsUser::class, $this->userId);
        assert($user instanceof CmsUser);

        $newGroup       = new CmsGroup();
        $newGroup->name = 'Test4';

        $user->addGroup($newGroup);
        $this->_em->persist($newGroup);

        $qc     = $this->getCurrentQueryCount();
        $groups = $user->groups->slice(0, 10);

        $this->assertEquals(4, count($groups));
        $this->assertEquals($qc + 1, $this->getCurrentQueryCount());
    }

    /**
     * @group DDC-1398
     * @group non-cacheable
     */
    public function testGetIndexByIdentifier(): void
    {
        $user = $this->_em->find(CmsUser::class, $this->userId);
        assert($user instanceof CmsUser);

        $queryCount  = $this->getCurrentQueryCount();
        $phonenumber = $user->phonenumbers->get($this->phonenumber);

        $this->assertFalse($user->phonenumbers->isInitialized());
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertSame($phonenumber, $this->_em->find(CmsPhonenumber::class, $this->phonenumber));

        $article = $user->phonenumbers->get($this->phonenumber);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount(), 'Getting the same entity should not cause an extra query to be executed');
    }

    /**
     * @group DDC-1398
     */
    public function testGetIndexByOneToMany(): void
    {
        $user = $this->_em->find(CmsUser::class, $this->userId);
        assert($user instanceof CmsUser);

        $queryCount = $this->getCurrentQueryCount();

        $article = $user->articles->get($this->topic);

        $this->assertFalse($user->articles->isInitialized());
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertSame($article, $this->_em->find(CmsArticle::class, $this->articleId));
    }

    /**
     * @group DDC-1398
     */
    public function testGetIndexByManyToManyInverseSide(): void
    {
        $group = $this->_em->find(CmsGroup::class, $this->groupId);
        assert($group instanceof CmsGroup);

        $queryCount = $this->getCurrentQueryCount();

        $user = $group->users->get($this->username);

        $this->assertFalse($group->users->isInitialized());
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertSame($user, $this->_em->find(CmsUser::class, $this->userId));
    }

    /**
     * @group DDC-1398
     */
    public function testGetIndexByManyToManyOwningSide(): void
    {
        $user = $this->_em->find(CmsUser::class, $this->userId);
        assert($user instanceof CmsUser);

        $queryCount = $this->getCurrentQueryCount();

        $group = $user->groups->get($this->groupname);

        $this->assertFalse($user->groups->isInitialized());
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertSame($group, $this->_em->find(CmsGroup::class, $this->groupId));
    }

    /**
     * @group DDC-1398
     */
    public function testGetNonExistentIndexBy(): void
    {
        $user = $this->_em->find(CmsUser::class, $this->userId);
        $this->assertNull($user->articles->get(-1));
        $this->assertNull($user->groups->get(-1));
    }

    public function testContainsKeyIndexByOneToMany(): void
    {
        $user = $this->_em->find(CmsUser::class, $this->userId);
        assert($user instanceof CmsUser);

        $queryCount = $this->getCurrentQueryCount();

        $contains = $user->articles->containsKey($this->topic);

        $this->assertTrue($contains);
        $this->assertFalse($user->articles->isInitialized());
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
    }

    public function testContainsKeyIndexByOneToManyJoinedInheritance(): void
    {
        $class                                                 = $this->_em->getClassMetadata(DDC2504OtherClass::class);
        $class->associationMappings['childClasses']['indexBy'] = 'id';

        $otherClass = $this->_em->find(DDC2504OtherClass::class, $this->ddc2504OtherClassId);

        $queryCount = $this->getCurrentQueryCount();

        $contains = $otherClass->childClasses->containsKey($this->ddc2504ChildClassId);

        $this->assertTrue($contains);
        $this->assertFalse($otherClass->childClasses->isInitialized());
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
    }

    public function testContainsKeyIndexByManyToMany(): void
    {
        $user = $this->_em->find(CmsUser::class, $this->userId2);

        $group = $this->_em->find(CmsGroup::class, $this->groupId);

        $queryCount = $this->getCurrentQueryCount();

        $contains = $user->groups->containsKey($group->name);

        $this->assertTrue($contains, 'The item is not into collection');
        $this->assertFalse($user->groups->isInitialized(), 'The collection must not be initialized');
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
    }

    public function testContainsKeyIndexByManyToManyNonOwning(): void
    {
        $user  = $this->_em->find(CmsUser::class, $this->userId2);
        $group = $this->_em->find(CmsGroup::class, $this->groupId);

        $queryCount = $this->getCurrentQueryCount();

        $contains = $group->users->containsKey($user->username);

        $this->assertTrue($contains, 'The item is not into collection');
        $this->assertFalse($group->users->isInitialized(), 'The collection must not be initialized');
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
    }

    public function testContainsKeyIndexByWithPkManyToMany(): void
    {
        $class                                           = $this->_em->getClassMetadata(CmsUser::class);
        $class->associationMappings['groups']['indexBy'] = 'id';

        $user = $this->_em->find(CmsUser::class, $this->userId2);

        $queryCount = $this->getCurrentQueryCount();

        $contains = $user->groups->containsKey($this->groupId);

        $this->assertTrue($contains, 'The item is not into collection');
        $this->assertFalse($user->groups->isInitialized(), 'The collection must not be initialized');
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
    }

    public function testContainsKeyIndexByWithPkManyToManyNonOwning(): void
    {
        $class                                          = $this->_em->getClassMetadata(CmsGroup::class);
        $class->associationMappings['users']['indexBy'] = 'id';

        $group = $this->_em->find(CmsGroup::class, $this->groupId);

        $queryCount = $this->getCurrentQueryCount();

        $contains = $group->users->containsKey($this->userId2);

        $this->assertTrue($contains, 'The item is not into collection');
        $this->assertFalse($group->users->isInitialized(), 'The collection must not be initialized');
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
    }

    public function testContainsKeyNonExistentIndexByOneToMany(): void
    {
        $user = $this->_em->find(CmsUser::class, $this->userId2);

        $queryCount = $this->getCurrentQueryCount();

        $contains = $user->articles->containsKey('NonExistentTopic');

        $this->assertFalse($contains);
        $this->assertFalse($user->articles->isInitialized());
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
    }

    public function testContainsKeyNonExistentIndexByManyToMany(): void
    {
        $user = $this->_em->find(CmsUser::class, $this->userId2);

        $queryCount = $this->getCurrentQueryCount();

        $contains = $user->groups->containsKey('NonExistentTopic');

        $this->assertFalse($contains);
        $this->assertFalse($user->groups->isInitialized());
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
    }

    private function loadFixture(): void
    {
        $user1           = new CmsUser();
        $user1->username = 'beberlei';
        $user1->name     = 'Benjamin';
        $user1->status   = 'active';

        $user2           = new CmsUser();
        $user2->username = 'jwage';
        $user2->name     = 'Jonathan';
        $user2->status   = 'active';

        $user3           = new CmsUser();
        $user3->username = 'romanb';
        $user3->name     = 'Roman';
        $user3->status   = 'active';

        $user4           = new CmsUser();
        $user4->username = 'gblanco';
        $user4->name     = 'Guilherme';
        $user4->status   = 'active';

        $this->_em->persist($user1);
        $this->_em->persist($user2);
        $this->_em->persist($user3);
        $this->_em->persist($user4);

        $group1       = new CmsGroup();
        $group1->name = 'Test1';

        $group2       = new CmsGroup();
        $group2->name = 'Test2';

        $group3       = new CmsGroup();
        $group3->name = 'Test3';

        $user1->addGroup($group1);
        $user1->addGroup($group2);
        $user1->addGroup($group3);

        $user2->addGroup($group1);
        $user3->addGroup($group1);
        $user4->addGroup($group1);

        $this->_em->persist($group1);
        $this->_em->persist($group2);
        $this->_em->persist($group3);

        $article1        = new CmsArticle();
        $article1->topic = 'Test1';
        $article1->text  = 'Test1';
        $article1->setAuthor($user1);

        $article2        = new CmsArticle();
        $article2->topic = 'Test2';
        $article2->text  = 'Test2';
        $article2->setAuthor($user1);

        $this->_em->persist($article1);
        $this->_em->persist($article2);

        $phonenumber1              = new CmsPhonenumber();
        $phonenumber1->phonenumber = '12345';

        $phonenumber2              = new CmsPhonenumber();
        $phonenumber2->phonenumber = '67890';

        $this->_em->persist($phonenumber1);
        $this->_em->persist($phonenumber2);

        $user1->addPhonenumber($phonenumber1);

        // DDC-2504
        $otherClass  = new DDC2504OtherClass();
        $childClass1 = new DDC2504ChildClass();
        $childClass2 = new DDC2504ChildClass();

        $childClass1->other = $otherClass;
        $childClass2->other = $otherClass;

        $otherClass->childClasses[] = $childClass1;
        $otherClass->childClasses[] = $childClass2;

        $this->_em->persist($childClass1);
        $this->_em->persist($childClass2);
        $this->_em->persist($otherClass);

        $this->_em->flush();
        $this->_em->clear();

        $this->articleId           = $article1->id;
        $this->userId              = $user1->getId();
        $this->userId2             = $user2->getId();
        $this->groupId             = $group1->id;
        $this->ddc2504OtherClassId = $otherClass->id;
        $this->ddc2504ChildClassId = $childClass1->id;

        $this->username    = $user1->username;
        $this->groupname   = $group1->name;
        $this->topic       = $article1->topic;
        $this->phonenumber = $phonenumber1->phonenumber;
    }
}
