<?php
declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Query;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group 5544
 */
class GH5544Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->_schemaTool->createSchema([$this->_em->getClassMetadata(GH5544UserBrowser::class)]);
    }

    public function testIssue(): void
    {
        $this->createData();
        $initialQueryCount = $this->getCurrentQueryCount();

        $query = $this->createQuery(false);
        self::assertSame(1, (int) $query->getSingleScalarResult());
        self::assertEquals($initialQueryCount + 1, $this->getCurrentQueryCount());

        $query = $this->createQuery(true);
        self::assertSame(1, (int) $query->getSingleScalarResult());
        self::assertEquals($initialQueryCount + 2, $this->getCurrentQueryCount());
    }

    private function createQuery(bool $distinct): Query
    {
        return $this->_em
            ->createQueryBuilder()
            ->select(sprintf(
                'COUNT(%s CONCAT(ub.userId, :concat_separator, ub.browser)) cnt',
                $distinct ? 'DISTINCT' : ''
            ))
            ->from(GH5544UserBrowser::class, 'ub')
            ->setParameter('concat_separator', '|')
            ->getQuery();
    }

    private function createData(): void
    {
        $this->_em->persist(new GH5544UserBrowser(123, 'Chrome'));
        $this->_em->flush();
        $this->_em->clear();
    }
}

/**
 * @Entity
 * @Table(name="GH5544_user_browser")
 */
class GH5544UserBrowser
{
    /**
     * @Id
     * @GeneratedValue("NONE")
     * @Column(type="integer")
     *
     * @var int
     */
    public $userId;

    /**
     * @Id
     * @GeneratedValue("NONE")
     * @Column(type="string", length=64)
     *
     * @var string
     */
    public $browser;

    /**
     * @param int    $userId
     * @param string $browser
     */
    public function __construct(int $userId, string $browser)
    {
        $this->userId = $userId;
        $this->browser = $browser;
    }
}
