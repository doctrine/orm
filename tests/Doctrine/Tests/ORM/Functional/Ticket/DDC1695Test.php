<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;

/**
 * @group DDC-1695
 */
class DDC1695Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        if ($this->em->getConnection()->getDatabasePlatform()->getName() != "sqlite") {
            $this->markTestSkipped("Only with sqlite");
        }
    }

    public function testIssue()
    {
        $dql = "SELECT n.smallText, n.publishDate FROM " . __NAMESPACE__ . "\\DDC1695News n";
        $sql = $this->em->createQuery($dql)->getSQL();

        self::assertEquals(
            'SELECT d0_."SmallText" AS SmallText_0, d0_."PublishDate" AS PublishDate_1 FROM "DDC1695News" d0_',
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
     * @var int
     *
     * @ORM\Column(name="IdNews", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $idNews;

    /**
     * @var int
     *
     * @ORM\Column(name="IdUser", type="bigint", nullable=false)
     */
    private $idUser;

    /**
     * @var int
     *
     * @ORM\Column(name="IdLanguage", type="integer", nullable=false)
     */
    private $idLanguage;

    /**
     * @var int
     *
     * @ORM\Column(name="IdCondition", type="integer", nullable=true)
     */
    private $idCondition;

    /**
     * @var int
     *
     * @ORM\Column(name="IdHealthProvider", type="integer", nullable=true)
     */
    private $idHealthProvider;

    /**
     * @var int
     *
     * @ORM\Column(name="IdSpeciality", type="integer", nullable=true)
     */
    private $idSpeciality;

    /**
     * @var int
     *
     * @ORM\Column(name="IdMedicineType", type="integer", nullable=true)
     */
    private $idMedicineType;

    /**
     * @var int
     *
     * @ORM\Column(name="IdTreatment", type="integer", nullable=true)
     */
    private $idTreatment;

    /**
     * @var string
     *
     * @ORM\Column(name="Title", type="string", nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="SmallText", type="string", nullable=true)
     */
    private $smallText;

    /**
     * @var string
     *
     * @ORM\Column(name="LongText", type="string", nullable=true)
     */
    private $longText;

    /**
     * @var DateTimeZone
     *
     * @ORM\Column(name="PublishDate", type="datetimetz", nullable=true)
     */
    private $publishDate;

    /**
     * @var array
     *
     * @ORM\Column(name="IdxNews", type="json_array", nullable=true)
     */
    private $idxNews;

    /**
     * @var bool
     *
     * @ORM\Column(name="Highlight", type="boolean", nullable=false)
     */
    private $highlight;

    /**
     * @var int
     *
     * @ORM\Column(name="Order", type="integer", nullable=false)
     */
    private $order;

    /**
     * @var bool
     *
     * @ORM\Column(name="Deleted", type="boolean", nullable=false)
     */
    private $deleted;

    /**
     * @var bool
     *
     * @ORM\Column(name="Active", type="boolean", nullable=false)
     */
    private $active;

    /**
     * @var bool
     *
     * @ORM\Column(name="UpdateToHighlighted", type="boolean", nullable=true)
     */
    private $updateToHighlighted;
}
