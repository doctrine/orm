<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\DDC3346\DDC3346Article;
use Doctrine\Tests\Models\DDC3346\DDC3346Author;
use Doctrine\Tests\OrmFunctionalTestCase;

use function assert;

/** @group DDC-3346 */
class DDC3346Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('ddc3346');

        parent::setUp();

        $this->loadAuthorFixture();
    }

    public function testFindOneWithEagerFetchWillNotHydrateLimitedCollection(): void
    {
        $author = $this->_em->getRepository(DDC3346Author::class)->findOneBy(
            ['username' => 'bwoogy']
        );
        assert($author instanceof DDC3346Author);

        self::assertCount(2, $author->articles);
    }

    public function testFindLimitedWithEagerFetchWillNotHydrateLimitedCollection(): void
    {
        /** @var DDC3346Author[] $authors */
        $authors = $this->_em->getRepository(DDC3346Author::class)->findBy(
            ['username' => 'bwoogy'],
            null,
            1
        );

        self::assertCount(1, $authors);
        self::assertCount(2, $authors[0]->articles);
    }

    public function testFindWithEagerFetchAndOffsetWillNotHydrateLimitedCollection(): void
    {
        /** @var DDC3346Author[] $authors */
        $authors = $this->_em->getRepository(DDC3346Author::class)->findBy(
            ['username' => 'bwoogy'],
            null,
            null,
            0 // using an explicitly defined offset
        );

        self::assertCount(1, $authors);
        self::assertCount(2, $authors[0]->articles);
    }

    private function loadAuthorFixture(): void
    {
        $user     = new DDC3346Author();
        $article1 = new DDC3346Article();
        $article2 = new DDC3346Article();

        $user->username   = 'bwoogy';
        $article1->user   = $user;
        $article2->user   = $user;
        $user->articles[] = $article1;
        $user->articles[] = $article2;

        $this->_em->persist($user);
        $this->_em->persist($article1);
        $this->_em->persist($article2);
        $this->_em->flush();
        $this->_em->clear();
    }
}
