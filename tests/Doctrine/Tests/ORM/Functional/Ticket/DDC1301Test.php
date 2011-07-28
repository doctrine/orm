<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @author asm89
 */
class DDC1301Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $userId;

    public function setUp()
    {
        $this->useModelSet('legacy');
        parent::setUp();

        $class = $this->_em->getClassMetadata('Doctrine\Tests\Models\Legacy\LegacyUser');
        $class->associationMappings['articles']['fetch'] = ClassMetadataInfo::FETCH_EXTRA_LAZY;
        $class->associationMappings['references']['fetch'] = ClassMetadataInfo::FETCH_EXTRA_LAZY;
        $class->associationMappings['cars']['fetch'] = ClassMetadataInfo::FETCH_EXTRA_LAZY;

        $this->loadFixture();
    }

    public function tearDown()
    {
        parent::tearDown();

        $class = $this->_em->getClassMetadata('Doctrine\Tests\Models\Legacy\LegacyUser');
        $class->associationMappings['articles']['fetch'] = ClassMetadataInfo::FETCH_LAZY;
        $class->associationMappings['references']['fetch'] = ClassMetadataInfo::FETCH_LAZY;
        $class->associationMappings['cars']['fetch'] = ClassMetadataInfo::FETCH_LAZY;
    }

    public function testCountNotInitializesLegacyCollection()
    {
        $user = $this->_em->find('Doctrine\Tests\Models\Legacy\LegacyUser', $this->userId);
        $queryCount = $this->getCurrentQueryCount();

        $this->assertFalse($user->articles->isInitialized());
        $this->assertEquals(2, count($user->articles));
        $this->assertFalse($user->articles->isInitialized());

        foreach ($user->articles AS $article) { }

        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount(), "Expecting two queries to be fired for count, then iteration.");
    }

    public function testCountNotInitializesLegacyCollectionWithForeignIdentifier()
    {
        $user = $this->_em->find('Doctrine\Tests\Models\Legacy\LegacyUser', $this->userId);
        $queryCount = $this->getCurrentQueryCount();

        $this->assertFalse($user->references->isInitialized());
        $this->assertEquals(2, count($user->references));
        $this->assertFalse($user->references->isInitialized());

        foreach ($user->references AS $reference) { }

        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount(), "Expecting two queries to be fired for count, then iteration.");
    }

    public function testCountNotInitializesLegacyManyToManyCollection()
    {
        $user = $this->_em->find('Doctrine\Tests\Models\Legacy\LegacyUser', $this->userId);
        $queryCount = $this->getCurrentQueryCount();

        $this->assertFalse($user->cars->isInitialized());
        $this->assertEquals(3, count($user->cars));
        $this->assertFalse($user->cars->isInitialized());

        foreach ($user->cars AS $reference) { }

        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount(), "Expecting two queries to be fired for count, then iteration.");
    }

    public function loadFixture()
    {
        $user1 = new \Doctrine\Tests\Models\Legacy\LegacyUser();
        $user1->username = "beberlei";
        $user1->name = "Benjamin";
        $user1->status = "active";

        $user2 = new \Doctrine\Tests\Models\Legacy\LegacyUser();
        $user2->username = "jwage";
        $user2->name = "Jonathan";
        $user2->status = "active";

        $user3 = new \Doctrine\Tests\Models\Legacy\LegacyUser();
        $user3->username = "romanb";
        $user3->name = "Roman";
        $user3->status = "active";

        $this->_em->persist($user1);
        $this->_em->persist($user2);
        $this->_em->persist($user3);

        $article1 = new \Doctrine\Tests\Models\Legacy\LegacyArticle();
        $article1->topic = "Test";
        $article1->text = "Test";
        $article1->setAuthor($user1);

        $article2 = new \Doctrine\Tests\Models\Legacy\LegacyArticle();
        $article2->topic = "Test";
        $article2->text = "Test";
        $article2->setAuthor($user1);

        $this->_em->persist($article1);
        $this->_em->persist($article2);

        $car1 = new \Doctrine\Tests\Models\Legacy\LegacyCar();
        $car1->description = "Test1";

        $car2 = new \Doctrine\Tests\Models\Legacy\LegacyCar();
        $car2->description = "Test2";

        $car3 = new \Doctrine\Tests\Models\Legacy\LegacyCar();
        $car3->description = "Test3";

        $user1->addCar($car1);
        $user1->addCar($car2);
        $user1->addCar($car3);

        $user2->addCar($car1);
        $user3->addCar($car1);

        $this->_em->persist($car1);
        $this->_em->persist($car2);
        $this->_em->persist($car3);

        $this->_em->flush();

        $detail1 = new \Doctrine\Tests\Models\Legacy\LegacyUserReference($user1, $user2, "foo");
        $detail2 = new \Doctrine\Tests\Models\Legacy\LegacyUserReference($user1, $user3, "bar");

        $this->_em->persist($detail1);
        $this->_em->persist($detail2);

        $this->_em->flush();
        $this->_em->clear();

        $this->userId = $user1->getId();
    }
}
