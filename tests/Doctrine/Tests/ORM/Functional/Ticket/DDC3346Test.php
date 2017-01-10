<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\DDC3346\DDC3346Article;
use Doctrine\Tests\Models\DDC3346\DDC3346Author;

/**
 * @group DDC-3346
 */
class DDC3346Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('ddc3346');

        parent::setUp();

        $this->loadAuthorFixture();
    }

    public function testFindOneWithEagerFetchWillNotHydrateLimitedCollection()
    {
        /* @var DDC3346Author $author */
        $author = $this->em->getRepository(DDC3346Author::class)->findOneBy(
            ['username' => 'bwoogy']
        );

        self::assertCount(2, $author->articles);
    }

    public function testFindLimitedWithEagerFetchWillNotHydrateLimitedCollection()
    {
        /* @var DDC3346Author[] $authors */
        $authors = $this->em->getRepository(DDC3346Author::class)->findBy(
            ['username' => 'bwoogy'],
            null,
            1
        );

        self::assertCount(1, $authors);
        self::assertCount(2, $authors[0]->articles);
    }

    public function testFindWithEagerFetchAndOffsetWillNotHydrateLimitedCollection()
    {
        /* @var DDC3346Author[] $authors */
        $authors = $this->em->getRepository(DDC3346Author::class)->findBy(
            ['username' => 'bwoogy'],
            null,
            null,
            0 // using an explicitly defined offset
        );

        self::assertCount(1, $authors);
        self::assertCount(2, $authors[0]->articles);
    }

    private function loadAuthorFixture()
    {
        $user     = new DDC3346Author();
        $article1 = new DDC3346Article();
        $article2 = new DDC3346Article();

        $user->username   = 'bwoogy';
        $article1->user   = $user;
        $article2->user   = $user;
        $user->articles[] = $article1;
        $user->articles[] = $article2;

        $this->em->persist($user);
        $this->em->persist($article1);
        $this->em->persist($article2);
        $this->em->flush();
        $this->em->clear();
    }
}
