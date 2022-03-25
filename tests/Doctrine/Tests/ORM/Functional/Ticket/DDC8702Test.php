<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Tests\OrmFunctionalTestCase;

use function assert;

class DDC8702Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(DDC8702Article::class);
    }

    public function testIssue(): void
    {
        //setup
        $article1 = new DDC8702Article();
        $article2 = new DDC8702Article();
        $article3 = new DDC8702Article();
        $article4 = new DDC8702Article();

        $this->_em->persist($article1);
        $this->_em->persist($article2);
        $this->_em->persist($article3);
        $this->_em->persist($article4);

        $this->_em->flush();
        $this->_em->clear();
        //end setup

        $qb = $this->_em->getRepository(DDC8702Article::class)->createQueryBuilder('a');

        assert($qb instanceof QueryBuilder);

        $qb->addCriteria(Criteria::create()->andWhere(Criteria::expr()->lte('created', new DateTimeImmutable('tomorrow'))));
        $qb->addCriteria(Criteria::create()->andWhere(Criteria::expr()->lte('created', new DateTimeImmutable('-2 hours'))));
        $qb->addCriteria(Criteria::create()->andWhere(Criteria::expr()->gte('created', new DateTimeImmutable('yesterday'))));

        $result = $qb->getQuery()->toIterable();
        self::assertCount(4, $result, 'invalid number of articles');

        $this->_em->clear();
    }
}

/**
 * @Entity
 * @Table(name="article")
 */
class DDC8702Article
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var DateTimeInterface
     * @Column(type="datetime_immutable")
     */
    private $created;

    public function __construct()
    {
        $this->created = new DateTimeImmutable('now');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }
}
