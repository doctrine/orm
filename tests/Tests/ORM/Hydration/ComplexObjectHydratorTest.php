<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\Models\ComplexHydration\Item;
use Doctrine\Tests\Models\ComplexHydration\ItemSerie;
use Doctrine\Tests\Models\ComplexHydration\Serie;
use Doctrine\Tests\Models\ComplexHydration\SerieImportator;
use Doctrine\Tests\OrmFunctionalTestCase;

use function count;
use function dirname;
use function sprintf;
use function substr;

/**
 * @requires PHP 8.1
 */
class ComplexObjectHydratorTest extends OrmFunctionalTestCase
{
    /** @var bool */
    private $entitiesCreated = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->_em         = $this->getEntityManager(null, new AttributeDriver([dirname(__DIR__, 2) . '/Models/ComplexHydration'], true));
        $this->_schemaTool = new SchemaTool($this->_em);

        if (! $this->entitiesCreated) {
            $this->entitiesCreated = true;

            $this->setUpEntitySchema([Serie::class, Item::class, SerieImportator::class, ItemSerie::class]);

            for ($iSerie = 1; $iSerie <= 2; $iSerie++) {
                $serie = new Serie();
                $serie->setLibelle('Serie ' . $iSerie);
                $this->_em->persist($serie);

                $serieImportator = new SerieImportator();
                $serieImportator->setLibelle('imp ' . $iSerie);
                $serieImportator->setSerie($serie);
                $this->_em->persist($serieImportator);

                for ($iItem = 1; $iItem <= 12; $iItem++) {
                    $item = new Item();
                    $item->setLibelle('Item ' . $iSerie . '.' . $iItem);

                    $itemSerie = new ItemSerie();
                    $itemSerie->setSerie($serie);
                    $itemSerie->setItem($item);
                    $itemSerie->setNumber('Num ' . $iSerie . '.' . $iItem);

                    $this->_em->persist($item);
                    $this->_em->persist($itemSerie);
                }
            }

            $this->_em->flush();
            $this->_em->clear();
        }
    }

    protected function tearDown(): void
    {
        $this->_em->createQuery('DELETE FROM ' . ItemSerie::class)->execute();
        $this->_em->createQuery('DELETE FROM ' . SerieImportator::class)->execute();
        $this->_em->createQuery('DELETE FROM ' . Serie::class)->execute();
        $this->_em->createQuery('DELETE FROM ' . Item::class)->execute();
    }

    protected function getDQL(): string
    {
        return sprintf(
            <<<'SQL'
               SELECT s0_, i1_, i2_, s4_, s5_, i6_
                 FROM %s s0_
            LEFT JOIN s0_.itemSeries i1_
            LEFT JOIN i1_.item i2_
            LEFT JOIN s0_.serieImportators s4_
            LEFT JOIN s4_.serie s5_
            LEFT JOIN s5_.itemSeries i6_
             ORDER BY i1_.serie ASC, i1_.item ASC
            SQL
            ,
            Serie::class
        );
    }

    public function testComplexEntityQueryInObject(): void
    {
        $dql  = $this->getDQL();
        $data = $this->_em->createQuery($dql)->getResult();

        self::assertEquals(2, count($data));

        for ($i = 0; $i < 2; $i++) {
            foreach ($data[$i]->getItemSeries()->toArray() as $itemSerie) {
                self::assertEquals('Num ', substr($itemSerie->getNumber(), 0, 4));
                self::assertEquals('Item ', substr($itemSerie->getItem()->getLibelle(), 0, 5));
                self::assertEquals(substr($itemSerie->getNumber(), 4), substr($itemSerie->getItem()->getLibelle(), 5));
            }
        }
    }

    public function testComplexEntityQueryInArray(): void
    {
        $dql  = $this->getDQL();
        $data = $this->_em->createQuery($dql)->getResult(AbstractQuery::HYDRATE_ARRAY);

        for ($i = 0; $i < 2; $i++) {
            foreach ($data[$i]['itemSeries'] as $itemSerie) {
                self::assertEquals('Num ', substr($itemSerie['number'], 0, 4));
                self::assertEquals('Item ', substr($itemSerie['item']['libelle'], 0, 5));
                self::assertEquals(substr($itemSerie['number'], 4), substr($itemSerie['item']['libelle'], 5));
            }
        }
    }
}
