<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('GH-11278')]
final class QueryParameterTest extends OrmFunctionalTestCase
{
    private int $userId;

    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();

        $user            = new CmsUser();
        $user->name      = 'John Doe';
        $user->username  = 'john';
        $user2           = new CmsUser();
        $user2->name     = 'Jane Doe';
        $user2->username = 'jane';
        $user3           = new CmsUser();
        $user3->name     = 'Just Bill';
        $user3->username = 'bill';

        $this->_em->persist($user);
        $this->_em->persist($user2);
        $this->_em->persist($user3);
        $this->_em->flush();

        $this->userId = $user->id;

        $this->_em->clear();
    }

    public function testParameterTypeInBuilder(): void
    {
        $result = $this->_em->createQueryBuilder()
            ->from(CmsUser::class, 'u')
            ->select('u.name')
            ->where('u.id = :id')
            ->setParameter('id', $this->userId, ParameterType::INTEGER)
            ->getQuery()
            ->getArrayResult();

        self::assertSame([['name' => 'John Doe']], $result);
    }

    public function testParameterTypeInQuery(): void
    {
        $result = $this->_em->createQueryBuilder()
            ->from(CmsUser::class, 'u')
            ->select('u.name')
            ->where('u.id = :id')
            ->getQuery()
            ->setParameter('id', $this->userId, ParameterType::INTEGER)
            ->getArrayResult();

        self::assertSame([['name' => 'John Doe']], $result);
    }

    public function testDbalTypeStringInBuilder(): void
    {
        $result = $this->_em->createQueryBuilder()
            ->from(CmsUser::class, 'u')
            ->select('u.name')
            ->where('u.id = :id')
            ->setParameter('id', $this->userId, Types::INTEGER)
            ->getQuery()
            ->getArrayResult();

        self::assertSame([['name' => 'John Doe']], $result);
    }

    public function testDbalTypeStringInQuery(): void
    {
        $result = $this->_em->createQueryBuilder()
            ->from(CmsUser::class, 'u')
            ->select('u.name')
            ->where('u.id = :id')
            ->getQuery()
            ->setParameter('id', $this->userId, Types::INTEGER)
            ->getArrayResult();

        self::assertSame([['name' => 'John Doe']], $result);
    }

    public function testArrayParameterTypeInBuilder(): void
    {
        $result = $this->_em->createQueryBuilder()
            ->from(CmsUser::class, 'u')
            ->select('u.name')
            ->where('u.username IN (:usernames)')
            ->orderBy('u.username')
            ->setParameter('usernames', ['john', 'jane'], ArrayParameterType::STRING)
            ->getQuery()
            ->getArrayResult();

        self::assertSame([['name' => 'Jane Doe'], ['name' => 'John Doe']], $result);
    }

    public function testArrayParameterTypeInQuery(): void
    {
        $result = $this->_em->createQueryBuilder()
            ->from(CmsUser::class, 'u')
            ->select('u.name')
            ->where('u.username IN (:usernames)')
            ->orderBy('u.username')
            ->getQuery()
            ->setParameter('usernames', ['john', 'jane'], ArrayParameterType::STRING)
            ->getArrayResult();

        self::assertSame([['name' => 'Jane Doe'], ['name' => 'John Doe']], $result);
    }
}
