<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * @group DDC-1695
 */
class DDC1695Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testIssue()
    {
        if ($this->_em->getConnection()->getDatabasePlatform()->getName() != "sqlite") {
            $this->markTestSkipped("Only with sqlite");
        }
        $dql = "SELECT n.smallText, n.publishDate FROM " . __NAMESPACE__ . "\\DDC1695News n";
        $sql = $this->_em->createQuery($dql)->getSQL();

        $this->assertEquals(
            'SELECT d0_."SmallText" AS SmallText_0, d0_."PublishDate" AS PublishDate_1 FROM "DDC1695News" d0_',
            $sql
        );
    }
}

/**
 * @Table(name="`DDC1695News`")
 * @Entity
 */
class DDC1695News
{
    /**
     * @var int $idNews
     *
     * @Column(name="`IdNews`", type="integer", nullable=false)
     * @Id
     * @GeneratedValue
     */
    private $idNews;

    /**
     * @var bigint $iduser
     *
     * @Column(name="`IdUser`", type="bigint", nullable=false)
     */
    private $idUser;

    /**
     * @var int $idLanguage
     *
     * @Column(name="`IdLanguage`", type="integer", nullable=false)
     */
    private $idLanguage;

    /**
     * @var int $idCondition
     *
     * @Column(name="`IdCondition`", type="integer", nullable=true)
     */
    private $idCondition;

    /**
     * @var int $idHealthProvider
     *
     * @Column(name="`IdHealthProvider`", type="integer", nullable=true)
     */
    private $idHealthProvider;

    /**
     * @var int $idSpeciality
     *
     * @Column(name="`IdSpeciality`", type="integer", nullable=true)
     */
    private $idSpeciality;

    /**
     * @var int $idMedicineType
     *
     * @Column(name="`IdMedicineType`", type="integer", nullable=true)
     */
    private $idMedicineType;

    /**
     * @var int $idTreatment
     *
     * @Column(name="`IdTreatment`", type="integer", nullable=true)
     */
    private $idTreatment;

    /**
     * @var string $title
     *
     * @Column(name="`Title`", type="string", nullable=true)
     */
    private $title;

    /**
     * @var string $smallText
     *
     * @Column(name="`SmallText`", type="string", nullable=true)
     */
    private $smallText;

    /**
     * @var string $longText
     *
     * @Column(name="`LongText`", type="string", nullable=true)
     */
    private $longText;

    /**
     * @var datetimetz $publishDate
     *
     * @Column(name="`PublishDate`", type="datetimetz", nullable=true)
     */
    private $publishDate;

    /**
     * @var tsvector $idxNews
     *
     * @Column(name="`IdxNews`", type="tsvector", nullable=true)
     */
    private $idxNews;

    /**
     * @var bool $highlight
     *
     * @Column(name="`Highlight`", type="boolean", nullable=false)
     */
    private $highlight;

    /**
     * @var int $order
     *
     * @Column(name="`Order`", type="integer", nullable=false)
     */
    private $order;

    /**
     * @var bool $deleted
     *
     * @Column(name="`Deleted`", type="boolean", nullable=false)
     */
    private $deleted;

    /**
     * @var bool $active
     *
     * @Column(name="`Active`", type="boolean", nullable=false)
     */
    private $active;

    /**
     * @var bool $updateToHighlighted
     *
     * @Column(name="`UpdateToHighlighted`", type="boolean", nullable=true)
     */
    private $updateToHighlighted;
}
