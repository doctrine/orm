<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use DateTimeZone;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-1695')]
class DDC1695Test extends OrmFunctionalTestCase
{
    public function testIssue(): void
    {
        if (! $this->_em->getConnection()->getDatabasePlatform() instanceof SQLitePlatform) {
            self::markTestSkipped('Only with sqlite');
        }

        $dql = 'SELECT n.smallText, n.publishDate FROM ' . __NAMESPACE__ . '\\DDC1695News n';
        $sql = $this->_em->createQuery($dql)->getSQL();

        self::assertEquals(
            'SELECT d0_."SmallText" AS SmallText_0, d0_."PublishDate" AS PublishDate_1 FROM "DDC1695News" d0_',
            $sql,
        );
    }
}

#[Table(name: '`DDC1695News`')]
#[Entity]
class DDC1695News
{
    #[Column(name: '`IdNews`', type: 'integer', nullable: false)]
    #[Id]
    #[GeneratedValue]
    private int $idNews;

    #[Column(name: '`IdUser`', type: 'bigint', nullable: false)]
    private int $idUser;

    #[Column(name: '`IdLanguage`', type: 'integer', nullable: false)]
    private int $idLanguage;

    #[Column(name: '`IdCondition`', type: 'integer', nullable: true)]
    private int $idCondition;

    #[Column(name: '`IdHealthProvider`', type: 'integer', nullable: true)]
    private int $idHealthProvider;

    #[Column(name: '`IdSpeciality`', type: 'integer', nullable: true)]
    private int $idSpeciality;

    #[Column(name: '`IdMedicineType`', type: 'integer', nullable: true)]
    private int $idMedicineType;

    #[Column(name: '`IdTreatment`', type: 'integer', nullable: true)]
    private int $idTreatment;

    #[Column(name: '`Title`', type: 'string', nullable: true)]
    private string $title;

    #[Column(name: '`SmallText`', type: 'string', nullable: true)]
    private string $smallText;

    #[Column(name: '`LongText`', type: 'string', nullable: true)]
    private string $longText;

    #[Column(name: '`PublishDate`', type: 'datetimetz', nullable: true)]
    private DateTimeZone $publishDate;

    #[Column(name: '`IdxNews`', type: 'json_array', nullable: true)]
    private array $idxNews;

    #[Column(name: '`Highlight`', type: 'boolean', nullable: false)]
    private bool $highlight;

    #[Column(name: '`Order`', type: 'integer', nullable: false)]
    private int $order;

    #[Column(name: '`Deleted`', type: 'boolean', nullable: false)]
    private bool $deleted;

    #[Column(name: '`Active`', type: 'boolean', nullable: false)]
    private bool $active;

    #[Column(name: '`UpdateToHighlighted`', type: 'boolean', nullable: true)]
    private bool $updateToHighlighted;
}
