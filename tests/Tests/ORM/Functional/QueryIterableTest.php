<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\IterableTester;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

final class QueryIterableTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();
    }

    public function testAlias(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->status   = 'developer';

        $this->_em->persist($user);
        $this->_em->flush();

        $query = $this->_em->createQuery('SELECT u AS user FROM Doctrine\Tests\Models\CMS\CmsUser u');

        $users = $query->getResult();
        self::assertCount(1, $users);

        self::assertEquals('gblanco', $users[0]['user']->username);

        $this->_em->clear();

        IterableTester::assertResultsAreTheSame($query);
    }

    public function testAliasInnerJoin(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->status   = 'developer';

        $address          = new CmsAddress();
        $address->country = 'Germany';
        $address->city    = 'Berlin';
        $address->zip     = '12345';

        $address->user = $user;
        $user->address = $address;

        $this->_em->persist($user);
        $this->_em->flush();

        $query = $this->_em->createQuery('SELECT u AS user, a AS address FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.address a');

        $users = $query->getResult();
        self::assertCount(1, $users);

        self::assertEquals('gblanco', $users[0]['user']->username);

        $this->_em->clear();

        IterableTester::assertResultsAreTheSame($query);
    }

    public function testIndexByQueryWithOneResult(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Antonio J.';
        $user->username = 'ajgarlag';
        $user->status   = 'developer';

        $this->_em->persist($user);
        $this->_em->flush();

        $query = $this->_em->createQuery('SELECT u FROM ' . CmsUser::class . ' u INDEX BY u.username');
        IterableTester::assertResultsAreTheSame($query);
    }

    public function testIndexByQueryWithMultipleResults(): void
    {
        $article1        = new CmsArticle();
        $article1->topic = 'Doctrine 2';
        $article1->text  = 'This is an introduction to Doctrine 2.';

        $article2        = new CmsArticle();
        $article2->topic = 'Symfony 2';
        $article2->text  = 'This is an introduction to Symfony 2.';

        $article3        = new CmsArticle();
        $article3->topic = 'Laminas';
        $article3->text  = 'This is an introduction to Laminas.';

        $article4        = new CmsArticle();
        $article4->topic = 'CodeIgniter';
        $article4->text  = 'This is an introduction to CodeIgniter.';

        $this->_em->persist($article1);
        $this->_em->persist($article2);
        $this->_em->persist($article3);
        $this->_em->persist($article4);

        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQuery('select a from ' . CmsArticle::class . ' a INDEX BY a.topic');
        IterableTester::assertResultsAreTheSame($query);
    }
}
