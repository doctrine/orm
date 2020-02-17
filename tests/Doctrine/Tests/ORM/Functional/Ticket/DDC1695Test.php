<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-1695
 */
class DDC1695Test extends OrmFunctionalTestCase
{
    public function setUp() : void
    {
        parent::setUp();

        if ($this->em->getConnection()->getDatabasePlatform()->getName() !== 'sqlite') {
            $this->markTestSkipped('Only with sqlite');
        }
    }

    public function testIssue() : void
    {
        $dql = 'SELECT n.smallText, n.publishDate FROM ' . __NAMESPACE__ . '\\DDC1695News n';
        $sql = $this->em->createQuery($dql)->getSQL();

        self::assertEquals(
            'SELECT t0."SmallText" AS c0, t0."PublishDate" AS c1 FROM "DDC1695News" t0',
            $sql
        );
    }
}

/**
 * @ORM\Table(name="DDC1695News")
 * @ORM\Entity
 */
class DDC1695News
{
    /**
     * @ORM\Column(name="IdNews", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue
     *
     * @var int
     */
    private $idNews;

    /**
     * @ORM\Column(name="IdUser", type="bigint", nullable=false)
     *
     * @var int
     */
    private $idUser;

    /**
     * @ORM\Column(name="IdLanguage", type="integer", nullable=false)
     *
     * @var int
     */
    private $idLanguage;

    /**
     * @ORM\Column(name="IdCondition", type="integer", nullable=true)
     *
     * @var int
     */
    private $idCondition;

    /**
     * @ORM\Column(name="IdHealthProvider", type="integer", nullable=true)
     *
     * @var int
     */
    private $idHealthProvider;

    /**
     * @ORM\Column(name="IdSpeciality", type="integer", nullable=true)
     *
     * @var int
     */
    private $idSpeciality;

    /**
     * @ORM\Column(name="IdMedicineType", type="integer", nullable=true)
     *
     * @var int
     */
    private $idMedicineType;

    /**
     * @ORM\Column(name="IdTreatment", type="integer", nullable=true)
     *
     * @var int
     */
    private $idTreatment;

    /**
     * @ORM\Column(name="Title", type="string", nullable=true)
     *
     * @var string
     */
    private $title;

    /**
     * @ORM\Column(name="SmallText", type="string", nullable=true)
     *
     * @var string
     */
    private $smallText;

    /**
     * @ORM\Column(name="LongText", type="string", nullable=true)
     *
     * @var string
     */
    private $longText;

    /**
     * @ORM\Column(name="PublishDate", type="datetimetz", nullable=true)
     *
     * @var DateTimeZone
     */
    private $publishDate;

    /**
     * @ORM\Column(name="IdxNews", type="json_array", nullable=true)
     *
     * @var array
     */
    private $idxNews;

    /**
     * @ORM\Column(name="Highlight", type="boolean", nullable=false)
     *
     * @var bool
     */
    private $highlight;

    /**
     * @ORM\Column(name="Order", type="integer", nullable=false)
     *
     * @var int
     */
    private $order;

    /**
     * @ORM\Column(name="Deleted", type="boolean", nullable=false)
     *
     * @var bool
     */
    private $deleted;

    /**
     * @ORM\Column(name="Active", type="boolean", nullable=false)
     *
     * @var bool
     */
    private $active;

    /**
     * @ORM\Column(name="UpdateToHighlighted", type="boolean", nullable=true)
     *
     * @var bool
     */
    private $updateToHighlighted;
}
