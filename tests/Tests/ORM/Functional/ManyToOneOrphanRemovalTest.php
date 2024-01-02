<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Tests\DbalExtensions\Connection;
use Doctrine\Tests\Models\OrnementalOrphanRemoval\Person;
use Doctrine\Tests\Models\OrnementalOrphanRemoval\PhoneNumber;
use Doctrine\Tests\OrmFunctionalTestCase;

use function count;

use const DIRECTORY_SEPARATOR;

/**
 * Tests a unidirectional many-to-one association mapping with orphan removal.
 */
class ManyToOneOrphanRemovalTest extends OrmFunctionalTestCase
{
    /** @var string */
    private $personId;

    /** @var array<string, list<class-string>> */
    protected static $modelSets = [
        'ornemental_orphan_removal' => [
            Person::class,
            PhoneNumber::class,
        ],
    ];

    protected function setUp(): void
    {
        $this->useModelSet('ornemental_orphan_removal');

        parent::setUp();

        $person     = new Person();
        $person->id = 'ca41a293-799f-4d68-bf79-626c3ad223ec';

        $phone1              = new PhoneNumber();
        $phone1->id          = 'f4132478-c492-4dfe-aab5-a5b79ae129e7';
        $phone1->phonenumber = '123456';

        $phone2              = new PhoneNumber();
        $phone2->id          = '7faa4cd3-a155-4fbf-bc42-aa4269a4454d';
        $phone2->phonenumber = '234567';

        $phone1->person = $person;
        $phone2->person = $person;

        $this->_em->persist($phone1);
        $this->_em->persist($phone2);
        $this->_em->persist($person);
        $this->_em->flush();

        $this->personId = $person->id;
        $this->_em->clear();
    }

    public function testOrphanRemovalIsPurelyOrnemental(): void
    {
        $person = $this->_em->getReference(Person::class, $this->personId);

        $this->_em->remove($person);
        $this->_em->flush();
        $this->_em->clear();

        $query  = $this->_em->createQuery(
            'SELECT u FROM Doctrine\Tests\Models\OrnementalOrphanRemoval\Person u'
        );
        $result = $query->getResult();

        self::assertEquals(0, count($result), 'Person should be removed by EntityManager');

        $query  = $this->_em->createQuery(
            'SELECT p FROM Doctrine\Tests\Models\OrnementalOrphanRemoval\PhoneNumber p'
        );
        $result = $query->getResult();

        self::assertEquals(2, count($result), 'Orphan removal should not kick in');
    }

    protected function getEntityManager(
        ?Connection $connection = null,
        ?MappingDriver $mappingDriver = null
    ): EntityManagerInterface {
        return parent::getEntityManager($connection, new XmlDriver(
            __DIR__ . DIRECTORY_SEPARATOR . 'xml'
        ));
    }
}
