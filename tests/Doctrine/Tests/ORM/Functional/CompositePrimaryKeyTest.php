<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Exception\MissingIdentifierField;
use Doctrine\ORM\Exception\UnrecognizedIdentifierFields;
use Doctrine\ORM\Query\QueryException;
use Doctrine\Tests\Models\Navigation\NavCountry;
use Doctrine\Tests\Models\Navigation\NavPhotos;
use Doctrine\Tests\Models\Navigation\NavPointOfInterest;
use Doctrine\Tests\Models\Navigation\NavTour;
use Doctrine\Tests\Models\Navigation\NavUser;
use Doctrine\Tests\OrmFunctionalTestCase;

class CompositePrimaryKeyTest extends OrmFunctionalTestCase
{
    public function setUp() : void
    {
        $this->useModelSet('navigation');
        parent::setUp();
    }

    public function putGermanysBrandenburderTor()
    {
        $country = new NavCountry('Germany');
        $this->em->persist($country);
        $poi = new NavPointOfInterest(100, 200, 'Brandenburger Tor', $country);
        $this->em->persist($poi);
        $this->em->flush();
        $this->em->clear();
    }

    public function putTripAroundEurope()
    {
        $poi = $this->em->find(NavPointOfInterest::class, ['lat' => 100, 'long' => 200]);

        $tour = new NavTour('Trip around Europe');
        $tour->addPointOfInterest($poi);

        $this->em->persist($tour);
        $this->em->flush();
        $this->em->clear();

        return $tour;
    }

    public function testPersistCompositePkEntity() : void
    {
        $this->putGermanysBrandenburderTor();

        $poi = $this->em->find(NavPointOfInterest::class, ['lat' => 100, 'long' => 200]);

        self::assertInstanceOf(NavPointOfInterest::class, $poi);
        self::assertEquals(100, $poi->getLat());
        self::assertEquals(200, $poi->getLong());
        self::assertEquals('Brandenburger Tor', $poi->getName());
    }

    /**
     * @group DDC-1651
     */
    public function testSetParameterCompositeKeyObject() : void
    {
        $this->putGermanysBrandenburderTor();

        $poi   = $this->em->find(NavPointOfInterest::class, ['lat' => 100, 'long' => 200]);
        $photo = new NavPhotos($poi, 'asdf');
        $this->em->persist($photo);
        $this->em->flush();
        $this->em->clear();

        $dql = 'SELECT t FROM Doctrine\Tests\Models\Navigation\NavPhotos t WHERE t.poi = ?1';

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('A single-valued association path expression to an entity with a composite primary key is not supported.');

        $sql = $this->em->createQuery($dql)->getSQL();
    }

    public function testIdentityFunctionWithCompositePrimaryKey() : void
    {
        $this->putGermanysBrandenburderTor();

        $poi   = $this->em->find(NavPointOfInterest::class, ['lat' => 100, 'long' => 200]);
        $photo = new NavPhotos($poi, 'asdf');
        $this->em->persist($photo);
        $this->em->flush();
        $this->em->clear();

        $dql    = "SELECT IDENTITY(p.poi, 'long') AS long, IDENTITY(p.poi, 'lat') AS lat FROM Doctrine\Tests\Models\Navigation\NavPhotos p";
        $result = $this->em->createQuery($dql)->getResult();

        self::assertCount(1, $result);
        self::assertEquals(200, $result[0]['long']);
        self::assertEquals(100, $result[0]['lat']);
    }

    public function testManyToManyCompositeRelation() : void
    {
        $this->putGermanysBrandenburderTor();
        $tour = $this->putTripAroundEurope();

        $tour = $this->em->find(NavTour::class, $tour->getId());

        self::assertCount(1, $tour->getPointOfInterests());
    }

    public function testCompositeDqlEagerFetching() : void
    {
        $this->putGermanysBrandenburderTor();
        $this->putTripAroundEurope();

        $dql = 'SELECT t, p, c '
             . 'FROM Doctrine\Tests\Models\Navigation\NavTour t '
             . 'INNER JOIN t.pois p '
             . 'INNER JOIN p.country c';

        $tours = $this->em->createQuery($dql)->getResult();

        self::assertCount(1, $tours);

        $pois = $tours[0]->getPointOfInterests();

        self::assertCount(1, $pois);
        self::assertEquals('Brandenburger Tor', $pois[0]->getName());
    }

    public function testCompositeCollectionMemberExpression() : void
    {
        // Test should not throw any kind of exception
        $this->putGermanysBrandenburderTor();
        $this->putTripAroundEurope();

        $dql = 'SELECT t '
             . 'FROM Doctrine\Tests\Models\Navigation\NavTour t '
             . ', Doctrine\Tests\Models\Navigation\NavPointOfInterest p '
             . 'WHERE p MEMBER OF t.pois';

        $query = $this->em->createQuery($dql);
        $tours = $query->getResult();

        self::assertCount(0, $tours);
    }

    public function testSpecifyUnknownIdentifierPrimaryKeyFails() : void
    {
        $this->expectException(MissingIdentifierField::class);
        $this->expectExceptionMessage('The identifier long is missing for a query of Doctrine\Tests\Models\Navigation\NavPointOfInterest');

        $poi = $this->em->find(NavPointOfInterest::class, ['key1' => 100]);
    }

    public function testUnrecognizedIdentifierFieldsOnGetReference() : void
    {
        $this->expectException(UnrecognizedIdentifierFields::class);
        $this->expectExceptionMessage('Unrecognized identifier fields: "key1"');

        $poi = $this->em->getReference(NavPointOfInterest::class, ['lat' => 10, 'long' => 20, 'key1' => 100]);
    }

    /**
     * @group DDC-1939
     */
    public function testDeleteCompositePersistentCollection() : void
    {
        $this->putGermanysBrandenburderTor();

        $poi = $this->em->find(NavPointOfInterest::class, ['lat' => 100, 'long' => 200]);
        $poi->addVisitor(new NavUser('test1'));
        $poi->addVisitor(new NavUser('test2'));

        $this->em->flush();

        $poi->getVisitors()->clear();

        $this->em->flush();
        $this->em->clear();

        $poi = $this->em->find(NavPointOfInterest::class, ['lat' => 100, 'long' => 200]);
        self::assertCount(0, $poi->getVisitors());
    }
}
