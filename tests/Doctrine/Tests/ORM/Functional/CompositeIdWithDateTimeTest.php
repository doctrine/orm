<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\OrmFunctionalTestCase;

use function array_map;

class CompositeIdWithDateTimeTest extends OrmFunctionalTestCase
{
    private const MODELS = [
        TvChannel::class,
        Programme::class,
    ];

    /**
     * @return array<int, ClassMetadata>
     */
    private function getModels(): array
    {
        return array_map(
            function ($class) {
                return $this->_em->getClassMetadata($class);
            },
            self::MODELS
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->_schemaTool->createSchema($this->getModels());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->_schemaTool->dropSchema($this->getModels());
    }

    public function testCompositeIdWithDateTime(): void
    {
        $tvChannel = new TvChannel();
        $this->_em->persist($tvChannel);

        $programme            = new Programme();
        $programme->tvChannel = $tvChannel;
        $programme->start     = new DateTimeImmutable('2021-02-22 12:00:00');
        $programme->name      = 'programme1';
        $this->_em->persist($programme);

        $this->_em->flush();
        $this->_em->clear();

        $programme = $this->_em->find(
            Programme::class,
            [
                'tvChannel' => $this->_em->getReference(TvChannel::class, $tvChannel->id),
                'start' => new DateTimeImmutable('2021-02-22 12:00:00'),
            ]
        );

        $this->assertNotNull($programme);
        $this->assertSame('programme1', $programme->name);
    }
}

/**
 * @Entity
 */
class TvChannel
{
    /**
     * @var int|null
     * @Column(type="integer")
     * @Id
     * @GeneratedValue
     */
    public $id;
}

/**
 * @Entity
 */
class Programme
{
    /**
     * @var int|null
     * @Id
     * @ManyToOne(targetEntity="TvChannel")
     */
    public $tvChannel;

    /**
     * @var DateTimeInterface|null
     * @Id
     * @Column(type="datetime_immutable")
     */
    public $start;

    /**
     * @var string|null
     * @Column()
     */
    public $name;
}
