<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Internal\Hydration\ArrayHydrator;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Mocks\ArrayResultFactory;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmTestCase;

class GH9185Test extends OrmTestCase
{
    /**
     * Sometimes, DBMS is ordering rows randomly.
     * This 'randomness' caused ArrayHydrator to return null values in one-to-many relationships
     * instead of correct values.
     *
     * SELECT PARTIAL a.{id, topic}, PARTIAL u.{id, username}, PARTIAL p.{phonenumber}
     * FROM CmsArticle a
     * INNER JOIN a.user u
     * INNER JOIN u.phonenumbers p
     */
    public function testMultipleFetchJoinWithInconsistentRowOrder(): void
    {
        $rsm = new ResultSetMapping();

        $rsm->addEntityResult(CmsArticle::class, 'a');
        $rsm->addJoinedEntityResult(
            CmsUser::class,
            'u',
            'a',
            'user'
        );
        $rsm->addJoinedEntityResult(
            CmsPhonenumber::class,
            'p',
            'u',
            'phonenumbers'
        );
        $rsm->addFieldResult('a', 'a__id', 'id');
        $rsm->addFieldResult('a', 'a__topic', 'topic');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__username', 'username');
        $rsm->addFieldResult('p', 'p__phonenumber', 'phonenumber');

        $resultSet = [
            [
                'a__id' => '1',
                'a__topic' => 'Article 1',
                'u__id' => '1',
                'u__username' => 'LinasRam',
                'p__phonenumber' => '1234567890',
            ],
            [
                'a__id' => '1',
                'a__topic' => 'Article 1',
                'u__id' => '1',
                'u__username' => 'LinasRam',
                'p__phonenumber' => '9876543210',
            ],
            [
                'a__id' => '2',
                'a__topic' => 'Article 2',
                'u__id' => '1',
                'u__username' => 'LinasRam',
                'p__phonenumber' => '9876543210', // Different order of phone number than in previous rows
            ],
            [
                'a__id' => '2',
                'a__topic' => 'Article 2',
                'u__id' => '1',
                'u__username' => 'LinasRam',
                'p__phonenumber' => '1234567890', // Different order of phone number than in previous rows
            ],
        ];

        $stmt     = ArrayResultFactory::createFromArray($resultSet);
        $hydrator = new ArrayHydrator($this->getTestEntityManager());
        $result   = $hydrator->hydrateAll($stmt, $rsm);

        $expected = [
            [
                'id' => 1,
                'topic' => 'Article 1',
                'user' => [
                    'id' => 1,
                    'username' => 'LinasRam',
                    'phonenumbers' => [
                        ['phonenumber' => '1234567890'],
                        ['phonenumber' => '9876543210'],
                    ],
                ],
            ],
            [
                'id' => 2,
                'topic' => 'Article 2',
                'user' => [
                    'id' => 1,
                    'username' => 'LinasRam',
                    'phonenumbers' => [
                        ['phonenumber' => '9876543210'],
                        ['phonenumber' => '1234567890'],
                    ],
                ],
            ],
        ];

        self::assertSame($expected, $result);
    }
}
