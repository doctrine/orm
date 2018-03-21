<?php
declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH7062Test extends OrmFunctionalTestCase
{
    private const SEASON_ID = 'season_18';
    private const TEAM_ID   = 'team_A';

    protected function setUp() : void
    {
        parent::setUp();

        $this->setUpEntitySchema(
            [
                GH7062Team::class,
                GH7062Season::class,
                GH7062Ranking::class,
                GH7062RankingPosition::class
            ]
        );
    }

    /**
     * @group 7062
     */
    public function testEntityWithAssociationKeyIdentityCanBeUpdated() : void
    {
        $this->createInitialRankingWithRelatedEntities();
        $this->modifyRanking();
        $this->verifyRanking();
    }

    private function createInitialRankingWithRelatedEntities() : void
    {
        $team    = new GH7062Team(self::TEAM_ID);
        $season  = new GH7062Season(self::SEASON_ID);

        $season->ranking = new GH7062Ranking($season, [$team]);

        $this->_em->persist($team);
        $this->_em->persist($season);
        $this->_em->flush();
        $this->_em->clear();

        foreach ($season->ranking->positions as $position) {
            self::assertSame(0, $position->points);
        }
    }

    private function modifyRanking() : void
    {
        /** @var GH7062Ranking $ranking */
        $ranking = $this->_em->find(GH7062Ranking::class, self::SEASON_ID);

        foreach ($ranking->positions as $position) {
            $position->points += 3;
        }

        $this->_em->flush();
        $this->_em->clear();
    }

    private function verifyRanking() : void
    {
        /** @var GH7062Season $season */
        $season = $this->_em->find(GH7062Season::class, self::SEASON_ID);
        self::assertInstanceOf(GH7062Season::class, $season);

        $ranking = $season->ranking;
        self::assertInstanceOf(GH7062Ranking::class, $ranking);

        foreach ($ranking->positions as $position) {
            self::assertSame(3, $position->points);
        }
    }
}

/**
 * Simple Entity whose identity is defined through another Entity (Season)
 *
 * @Entity
 * @Table(name="soccer_rankings")
 */
class GH7062Ranking
{
    /**
     * @Id
     * @OneToOne(targetEntity=GH7062Season::class, inversedBy="ranking")
     * @JoinColumn(name="season", referencedColumnName="id")
     *
     * @var GH7062Season
     */
    public $season;

    /**
     * @OneToMany(targetEntity=GH7062RankingPosition::class, mappedBy="ranking", cascade={"all"})
     *
     * @var Collection|GH7062RankingPosition[]
     */
    public $positions;

    /**
     * @param GH7062Team[] $teams
     */
    public function __construct(GH7062Season $season, array $teams)
    {
        $this->season    = $season;
        $this->positions = new ArrayCollection();

        foreach ($teams as $team) {
            $this->positions[] = new GH7062RankingPosition($this, $team);
        }
    }
}

/**
 * Entity which serves as a identity provider for other entities
 *
 * @Entity
 * @Table(name="soccer_seasons")
 */
class GH7062Season
{
    /**
     * @Id
     * @Column(type="string")
     *
     * @var string
     */
    public $id;

    /**
     * @OneToOne(targetEntity=GH7062Ranking::class, mappedBy="season", cascade={"all"})
     *
     * @var GH7062Ranking|null
     */
    public $ranking;

    public function __construct(string $id)
    {
        $this->id = $id;
    }
}

/**
 * Entity which serves as a identity provider for other entities
 *
 * @Entity
 * @Table(name="soccer_teams")
 */
class GH7062Team
{
    /**
     * @Id
     * @Column(type="string")
     *
     * @var string
     */
    public $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }
}

/**
 * Entity whose identity is defined through two other entities
 *
 * @Entity
 * @Table(name="soccer_ranking_positions")
 */
class GH7062RankingPosition
{
    /**
     * @Id
     * @ManyToOne(targetEntity=GH7062Ranking::class, inversedBy="positions")
     * @JoinColumn(name="season", referencedColumnName="season")
     *
     * @var GH7062Ranking
     */
    public $ranking;

    /**
     * @Id
     * @ManyToOne(targetEntity=GH7062Team::class)
     * @JoinColumn(name="team_id", referencedColumnName="id")
     *
     * @var GH7062Team
     */
    public $team;

    /**
     * @Column(type="integer")
     *
     * @var int
     */
    public $points;

    public function __construct(GH7062Ranking $ranking, GH7062Team $team)
    {
        $this->ranking = $ranking;
        $this->team    = $team;
        $this->points  = 0;
    }
}
