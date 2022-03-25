<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;

use function assert;

class DDC8702Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(Article::class);
    }

    public function testIssue(): void
    {
        //setup
        $article1 = new Article();
        $article2 = new Article();
        $article3 = new Article();
        $article4 = new Article();

        $this->_em->persist($article1);
        $this->_em->persist($article2);
        $this->_em->persist($article3);
        $this->_em->persist($article4);

        $this->_em->flush();
        $this->_em->clear();
        //end setup

        $qb = $this->_em->getRepository(Article::class)->createQueryBuilder('a');

        $qb->addCriteria(Criteria::create()->andWhere(Criteria::expr()->lte('created', new \DateTimeImmutable('tomorrow'))));
        $qb->addCriteria(Criteria::create()->andWhere(Criteria::expr()->lte('created', new \DateTimeImmutable('-2 hours'))));
        $qb->addCriteria(Criteria::create()->andWhere(Criteria::expr()->gte('created', new \DateTimeImmutable('yesterday'))));

        $result = $qb->getQuery()->toIterable();
        self::assertCount(4, $result, "invalid number of articles");

        $this->_em->clear();
    }
}

/**
 * @Entity
 * @Table(name="article")
 */
class Article
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private ?int $id;

    /**
     * @var \DateTimeInterface
     * @Column(type="datetime_immutable")
     */
    private \DateTimeInterface $dateTime;

    public function __construct()
    {
        $this->dateTime = new \DateTimeImmutable('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->dateTime;
    }
}
