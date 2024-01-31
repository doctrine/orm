<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Query;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH11128Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH11128Owner::class,
            GH11128Inverse::class,
        ]);
    }

    public function testSetFetchModeEagerWhenNotEagerInMapping(): void
    {
        $query = $this->_em->createQueryBuilder()
            ->select('o', 'i')
            ->from(GH11128Owner::class, 'o')
            ->innerJoin('o.inverseLazy', 'i', Query\Expr\Join::WITH, 'i.field IS NOT NULL')
            ->getQuery();
        $query->setFetchMode(GH11128Owner::class, 'inverseLazy', ORM\ClassMetadataInfo::FETCH_EAGER);
        $this->expectExceptionMessage('Associations with fetch-mode=EAGER may not be using WITH conditions in
             "' . GH11128Owner::class . '#inverseLazy".');
        $query->getSql();
    }

    public function testSetFetchModeNotEagerWhenEagerInMapping(): void
    {
        $query = $this->_em->createQueryBuilder()
            ->select('o', 'i')
            ->from(GH11128Owner::class, 'o')
            ->innerJoin('o.inverseEager', 'i', Query\Expr\Join::WITH, 'i.field IS NOT NULL')
            ->getQuery();
        $query->setFetchMode(GH11128Owner::class, 'inverseEager', ORM\ClassMetadataInfo::FETCH_LAZY);
        $this->assertIsString($query->getSql());
    }
}

/**
 * @ORM\Entity
 */
class GH11128Owner
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var ?int
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="GH11128Inverse", fetch="EAGER")
     *
     * @var GH11128Inverse
     */
    private $inverseEager;

    /**
     * @ORM\ManyToOne(targetEntity="GH11128Inverse", fetch="LAZY")
     *
     * @var GH11128Inverse
     */
    private $inverseLazy;
}

/**
 * @ORM\Entity
 */
class GH11128Inverse
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var ?int
     */
    public $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @var string
     */
    private $field;
}
