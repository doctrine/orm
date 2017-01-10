<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FetchMode;
use Doctrine\Tests\Models;

/**
 * @author asm89
 *
 * @group non-cacheable
 * @group DDC-1301
 */
class DDC1301Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $userId;

    public function setUp()
    {
        $this->useModelSet('legacy');
        
        parent::setUp();

        $class = $this->em->getClassMetadata(Models\Legacy\LegacyUser::class);
        
        $class->associationMappings['articles']['fetch'] = FetchMode::EXTRA_LAZY;
        $class->associationMappings['references']['fetch'] = FetchMode::EXTRA_LAZY;
        $class->associationMappings['cars']['fetch'] = FetchMode::EXTRA_LAZY;

        $this->loadFixture();
    }

    public function tearDown()
    {
        parent::tearDown();

        $class = $this->em->getClassMetadata(Models\Legacy\LegacyUser::class);
        
        $class->associationMappings['articles']['fetch'] = FetchMode::LAZY;
        $class->associationMappings['references']['fetch'] = FetchMode::LAZY;
        $class->associationMappings['cars']['fetch'] = FetchMode::LAZY;
    }

    public function testCountNotInitializesLegacyCollection()
    {
        $user = $this->em->find(Models\Legacy\LegacyUser::class, $this->userId);
        $queryCount = $this->getCurrentQueryCount();

        self::assertFalse($user->articles->isInitialized());
        self::assertEquals(2, count($user->articles));
        self::assertFalse($user->articles->isInitialized());

        foreach ($user->articles AS $article) { }

        self::assertEquals($queryCount + 2, $this->getCurrentQueryCount(), "Expecting two queries to be fired for count, then iteration.");
    }

    public function testCountNotInitializesLegacyCollectionWithForeignIdentifier()
    {
        $user = $this->em->find(Models\Legacy\LegacyUser::class, $this->userId);
        $queryCount = $this->getCurrentQueryCount();

        self::assertFalse($user->references->isInitialized());
        self::assertEquals(2, count($user->references));
        self::assertFalse($user->references->isInitialized());

        foreach ($user->references AS $reference) { }

        self::assertEquals($queryCount + 2, $this->getCurrentQueryCount(), "Expecting two queries to be fired for count, then iteration.");
    }

    public function testCountNotInitializesLegacyManyToManyCollection()
    {
        $user = $this->em->find(Models\Legacy\LegacyUser::class, $this->userId);
        $queryCount = $this->getCurrentQueryCount();

        self::assertFalse($user->cars->isInitialized());
        self::assertEquals(3, count($user->cars));
        self::assertFalse($user->cars->isInitialized());

        foreach ($user->cars AS $reference) { }

        self::assertEquals($queryCount + 2, $this->getCurrentQueryCount(), "Expecting two queries to be fired for count, then iteration.");
    }

    public function loadFixture()
    {
        $user1 = new Models\Legacy\LegacyUser();
        $user1->username = "beberlei";
        $user1->name = "Benjamin";
        $user1->status = "active";

        $user2 = new Models\Legacy\LegacyUser();
        $user2->username = "jwage";
        $user2->name = "Jonathan";
        $user2->status = "active";

        $user3 = new Models\Legacy\LegacyUser();
        $user3->username = "romanb";
        $user3->name = "Roman";
        $user3->status = "active";

        $this->em->persist($user1);
        $this->em->persist($user2);
        $this->em->persist($user3);

        $article1 = new Models\Legacy\LegacyArticle();
        $article1->topic = "Test";
        $article1->text = "Test";
        $article1->setAuthor($user1);

        $article2 = new Models\Legacy\LegacyArticle();
        $article2->topic = "Test";
        $article2->text = "Test";
        $article2->setAuthor($user1);

        $this->em->persist($article1);
        $this->em->persist($article2);

        $car1 = new Models\Legacy\LegacyCar();
        $car1->description = "Test1";

        $car2 = new Models\Legacy\LegacyCar();
        $car2->description = "Test2";

        $car3 = new Models\Legacy\LegacyCar();
        $car3->description = "Test3";

        $user1->addCar($car1);
        $user1->addCar($car2);
        $user1->addCar($car3);

        $user2->addCar($car1);
        $user3->addCar($car1);

        $this->em->persist($car1);
        $this->em->persist($car2);
        $this->em->persist($car3);

        $this->em->flush();

        $detail1 = new Models\Legacy\LegacyUserReference($user1, $user2, "foo");
        $detail2 = new Models\Legacy\LegacyUserReference($user1, $user3, "bar");

        $this->em->persist($detail1);
        $this->em->persist($detail2);

        $this->em->flush();
        $this->em->clear();

        $this->userId = $user1->getId();
    }
}
