<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Models;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group non-cacheable
 * @group DDC-1301
 */
class DDC1301Test extends OrmFunctionalTestCase
{
    /** @var int */
    private $userId;

    protected function setUp(): void
    {
        $this->useModelSet('legacy');

        parent::setUp();

        $class                                             = $this->_em->getClassMetadata(Models\Legacy\LegacyUser::class);
        $class->associationMappings['articles']['fetch']   = ClassMetadata::FETCH_EXTRA_LAZY;
        $class->associationMappings['references']['fetch'] = ClassMetadata::FETCH_EXTRA_LAZY;
        $class->associationMappings['cars']['fetch']       = ClassMetadata::FETCH_EXTRA_LAZY;

        $this->loadFixture();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $class                                             = $this->_em->getClassMetadata(Models\Legacy\LegacyUser::class);
        $class->associationMappings['articles']['fetch']   = ClassMetadata::FETCH_LAZY;
        $class->associationMappings['references']['fetch'] = ClassMetadata::FETCH_LAZY;
        $class->associationMappings['cars']['fetch']       = ClassMetadata::FETCH_LAZY;
    }

    public function testCountNotInitializesLegacyCollection(): void
    {
        $user = $this->_em->find(Models\Legacy\LegacyUser::class, $this->userId);
        $this->getQueryLog()->reset()->enable();

        self::assertFalse($user->articles->isInitialized());
        self::assertCount(2, $user->articles);
        self::assertFalse($user->articles->isInitialized());

        foreach ($user->articles as $article) {
        }

        $this->assertQueryCount(2, 'Expecting two queries to be fired for count, then iteration.');
    }

    public function testCountNotInitializesLegacyCollectionWithForeignIdentifier(): void
    {
        $user = $this->_em->find(Models\Legacy\LegacyUser::class, $this->userId);
        $this->getQueryLog()->reset()->enable();

        self::assertFalse($user->references->isInitialized());
        self::assertCount(2, $user->references);
        self::assertFalse($user->references->isInitialized());

        foreach ($user->references as $reference) {
        }

        $this->assertQueryCount(2, 'Expecting two queries to be fired for count, then iteration.');
    }

    public function testCountNotInitializesLegacyManyToManyCollection(): void
    {
        $user = $this->_em->find(Models\Legacy\LegacyUser::class, $this->userId);
        $this->getQueryLog()->reset()->enable();

        self::assertFalse($user->cars->isInitialized());
        self::assertCount(3, $user->cars);
        self::assertFalse($user->cars->isInitialized());

        foreach ($user->cars as $reference) {
        }

        $this->assertQueryCount(2, 'Expecting two queries to be fired for count, then iteration.');
    }

    public function loadFixture(): void
    {
        $user1           = new Models\Legacy\LegacyUser();
        $user1->username = 'beberlei';
        $user1->name     = 'Benjamin';

        $user2           = new Models\Legacy\LegacyUser();
        $user2->username = 'jwage';
        $user2->name     = 'Jonathan';

        $user3           = new Models\Legacy\LegacyUser();
        $user3->username = 'romanb';
        $user3->name     = 'Roman';

        $this->_em->persist($user1);
        $this->_em->persist($user2);
        $this->_em->persist($user3);

        $article1        = new Models\Legacy\LegacyArticle();
        $article1->topic = 'Test';
        $article1->text  = 'Test';
        $article1->setAuthor($user1);

        $article2        = new Models\Legacy\LegacyArticle();
        $article2->topic = 'Test';
        $article2->text  = 'Test';
        $article2->setAuthor($user1);

        $this->_em->persist($article1);
        $this->_em->persist($article2);

        $car1              = new Models\Legacy\LegacyCar();
        $car1->description = 'Test1';

        $car2              = new Models\Legacy\LegacyCar();
        $car2->description = 'Test2';

        $car3              = new Models\Legacy\LegacyCar();
        $car3->description = 'Test3';

        $user1->addCar($car1);
        $user1->addCar($car2);
        $user1->addCar($car3);

        $user2->addCar($car1);
        $user3->addCar($car1);

        $this->_em->persist($car1);
        $this->_em->persist($car2);
        $this->_em->persist($car3);

        $this->_em->flush();

        $detail1 = new Models\Legacy\LegacyUserReference($user1, $user2, 'foo');
        $detail2 = new Models\Legacy\LegacyUserReference($user1, $user3, 'bar');

        $this->_em->persist($detail1);
        $this->_em->persist($detail2);

        $this->_em->flush();
        $this->_em->clear();

        $this->userId = $user1->getId();
    }
}
