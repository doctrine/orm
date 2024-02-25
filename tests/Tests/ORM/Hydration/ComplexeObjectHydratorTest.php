<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\Models\ComplexeHydration\Item;
use Doctrine\Tests\Models\ComplexeHydration\ItemSerie;
use Doctrine\Tests\Models\ComplexeHydration\Serie;
use Doctrine\Tests\Models\ComplexeHydration\SerieImportator;
use Doctrine\Tests\OrmFunctionalTestCase;

use function count;
use function dirname;

class ComplexeObjectHydratorTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->_em         = $this->getEntityManager(null, new AttributeDriver([dirname(__DIR__, 2) . '/Models/ComplexeHydration'], true));
        $this->_schemaTool = new SchemaTool($this->_em);

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

    public function testComplexEntityQueryInObject(): void
    {
        $dql  = 'SELECT s0_, i1_, i2_, s4_, s5_, i6_ FROM ' . Serie::class . ' s0_ LEFT JOIN s0_.itemSeries i1_ LEFT JOIN i1_.item i2_ LEFT JOIN s0_.serieImportators s4_ LEFT JOIN s4_.serie s5_ LEFT JOIN s5_.itemSeries i6_';
        $data = $this->_em->createQuery($dql)->getResult();

        self::assertEquals(2, count($data));

        self::assertEquals('Num 1.1', $data[0]->getItemSeries()->toArray()[0]->getNumber());
        self::assertEquals('Item 1.1', $data[0]->getItemSeries()->toArray()[0]->getItem()->getLibelle());

        self::assertEquals('Num 1.2', $data[0]->getItemSeries()->toArray()[1]->getNumber());
        self::assertEquals('Item 1.2', $data[0]->getItemSeries()->toArray()[1]->getItem()->getLibelle());

        self::assertEquals('Num 1.3', $data[0]->getItemSeries()->toArray()[2]->getNumber());
        self::assertEquals('Item 1.3', $data[0]->getItemSeries()->toArray()[2]->getItem()->getLibelle());

        self::assertEquals('Num 1.4', $data[0]->getItemSeries()->toArray()[3]->getNumber());
        self::assertEquals('Item 1.4', $data[0]->getItemSeries()->toArray()[3]->getItem()->getLibelle());

        self::assertEquals('Num 1.5', $data[0]->getItemSeries()->toArray()[4]->getNumber());
        self::assertEquals('Item 1.5', $data[0]->getItemSeries()->toArray()[4]->getItem()->getLibelle());

        self::assertEquals('Num 1.6', $data[0]->getItemSeries()->toArray()[5]->getNumber());
        self::assertEquals('Item 1.6', $data[0]->getItemSeries()->toArray()[5]->getItem()->getLibelle());

        self::assertEquals('Num 1.7', $data[0]->getItemSeries()->toArray()[6]->getNumber());
        self::assertEquals('Item 1.7', $data[0]->getItemSeries()->toArray()[6]->getItem()->getLibelle());

        self::assertEquals('Num 1.8', $data[0]->getItemSeries()->toArray()[7]->getNumber());
        self::assertEquals('Item 1.8', $data[0]->getItemSeries()->toArray()[7]->getItem()->getLibelle());

        self::assertEquals('Num 1.9', $data[0]->getItemSeries()->toArray()[8]->getNumber());
        self::assertEquals('Item 1.9', $data[0]->getItemSeries()->toArray()[8]->getItem()->getLibelle());

        self::assertEquals('Num 1.10', $data[0]->getItemSeries()->toArray()[9]->getNumber());
        self::assertEquals('Item 1.10', $data[0]->getItemSeries()->toArray()[9]->getItem()->getLibelle());

        self::assertEquals('Num 1.11', $data[0]->getItemSeries()->toArray()[10]->getNumber());
        self::assertEquals('Item 1.11', $data[0]->getItemSeries()->toArray()[10]->getItem()->getLibelle());

        self::assertEquals('Num 1.12', $data[0]->getItemSeries()->toArray()[11]->getNumber());
        self::assertEquals('Item 1.12', $data[0]->getItemSeries()->toArray()[11]->getItem()->getLibelle());

        self::assertEquals('Num 2.1', $data[1]->getItemSeries()->toArray()[0]->getNumber());
        self::assertEquals('Item 2.1', $data[1]->getItemSeries()->toArray()[0]->getItem()->getLibelle());

        self::assertEquals('Num 2.2', $data[1]->getItemSeries()->toArray()[1]->getNumber());
        self::assertEquals('Item 2.2', $data[1]->getItemSeries()->toArray()[1]->getItem()->getLibelle());

        self::assertEquals('Num 2.3', $data[1]->getItemSeries()->toArray()[2]->getNumber());
        self::assertEquals('Item 2.3', $data[1]->getItemSeries()->toArray()[2]->getItem()->getLibelle());

        self::assertEquals('Num 2.4', $data[1]->getItemSeries()->toArray()[3]->getNumber());
        self::assertEquals('Item 2.4', $data[1]->getItemSeries()->toArray()[3]->getItem()->getLibelle());

        self::assertEquals('Num 2.5', $data[1]->getItemSeries()->toArray()[4]->getNumber());
        self::assertEquals('Item 2.5', $data[1]->getItemSeries()->toArray()[4]->getItem()->getLibelle());

        self::assertEquals('Num 2.6', $data[1]->getItemSeries()->toArray()[5]->getNumber());
        self::assertEquals('Item 2.6', $data[1]->getItemSeries()->toArray()[5]->getItem()->getLibelle());

        self::assertEquals('Num 2.7', $data[1]->getItemSeries()->toArray()[6]->getNumber());
        self::assertEquals('Item 2.7', $data[1]->getItemSeries()->toArray()[6]->getItem()->getLibelle());

        self::assertEquals('Num 2.8', $data[1]->getItemSeries()->toArray()[7]->getNumber());
        self::assertEquals('Item 2.8', $data[1]->getItemSeries()->toArray()[7]->getItem()->getLibelle());

        self::assertEquals('Num 2.9', $data[1]->getItemSeries()->toArray()[8]->getNumber());
        self::assertEquals('Item 2.9', $data[1]->getItemSeries()->toArray()[8]->getItem()->getLibelle());

        self::assertEquals('Num 2.10', $data[1]->getItemSeries()->toArray()[9]->getNumber());
        self::assertEquals('Item 2.10', $data[1]->getItemSeries()->toArray()[9]->getItem()->getLibelle());

        self::assertEquals('Num 2.11', $data[1]->getItemSeries()->toArray()[10]->getNumber());
        self::assertEquals('Item 2.11', $data[1]->getItemSeries()->toArray()[10]->getItem()->getLibelle());

        self::assertEquals('Num 2.12', $data[1]->getItemSeries()->toArray()[11]->getNumber());
        self::assertEquals('Item 2.12', $data[1]->getItemSeries()->toArray()[11]->getItem()->getLibelle());
    }

    public function testComplexEntityQueryInArray(): void
    {
        $dql  = 'SELECT s0_, i1_, i2_, s4_, s5_, i6_ FROM ' . Serie::class . ' s0_ LEFT JOIN s0_.itemSeries i1_ LEFT JOIN i1_.item i2_ LEFT JOIN s0_.serieImportators s4_ LEFT JOIN s4_.serie s5_ LEFT JOIN s5_.itemSeries i6_';
        $data = $this->_em->createQuery($dql)->getResult(AbstractQuery::HYDRATE_ARRAY);

        self::assertEquals(2, count($data));

        self::assertEquals('Num 1.1', $data[0]['itemSeries'][0]['number']);
        self::assertEquals('Item 1.1', $data[0]['itemSeries'][0]['item']['libelle']);

        self::assertEquals('Num 1.2', $data[0]['itemSeries'][1]['number']);
        self::assertEquals('Item 1.2', $data[0]['itemSeries'][1]['item']['libelle']);

        self::assertEquals('Num 1.3', $data[0]['itemSeries'][2]['number']);
        self::assertEquals('Item 1.3', $data[0]['itemSeries'][2]['item']['libelle']);

        self::assertEquals('Num 1.4', $data[0]['itemSeries'][3]['number']);
        self::assertEquals('Item 1.4', $data[0]['itemSeries'][3]['item']['libelle']);

        self::assertEquals('Num 1.5', $data[0]['itemSeries'][4]['number']);
        self::assertEquals('Item 1.5', $data[0]['itemSeries'][4]['item']['libelle']);

        self::assertEquals('Num 1.6', $data[0]['itemSeries'][5]['number']);
        self::assertEquals('Item 1.6', $data[0]['itemSeries'][5]['item']['libelle']);

        self::assertEquals('Num 1.7', $data[0]['itemSeries'][6]['number']);
        self::assertEquals('Item 1.7', $data[0]['itemSeries'][6]['item']['libelle']);

        self::assertEquals('Num 1.8', $data[0]['itemSeries'][7]['number']);
        self::assertEquals('Item 1.8', $data[0]['itemSeries'][7]['item']['libelle']);

        self::assertEquals('Num 1.9', $data[0]['itemSeries'][8]['number']);
        self::assertEquals('Item 1.9', $data[0]['itemSeries'][8]['item']['libelle']);

        self::assertEquals('Num 1.10', $data[0]['itemSeries'][9]['number']);
        self::assertEquals('Item 1.10', $data[0]['itemSeries'][9]['item']['libelle']);

        self::assertEquals('Num 1.11', $data[0]['itemSeries'][10]['number']);
        self::assertEquals('Item 1.11', $data[0]['itemSeries'][10]['item']['libelle']);

        self::assertEquals('Num 1.12', $data[0]['itemSeries'][11]['number']);
        self::assertEquals('Item 1.12', $data[0]['itemSeries'][11]['item']['libelle']);

        self::assertEquals('Num 2.1', $data[1]['itemSeries'][0]['number']);
        self::assertEquals('Item 2.1', $data[1]['itemSeries'][0]['item']['libelle']);

        self::assertEquals('Num 2.2', $data[1]['itemSeries'][1]['number']);
        self::assertEquals('Item 2.2', $data[1]['itemSeries'][1]['item']['libelle']);

        self::assertEquals('Num 2.3', $data[1]['itemSeries'][2]['number']);
        self::assertEquals('Item 2.3', $data[1]['itemSeries'][2]['item']['libelle']);

        self::assertEquals('Num 2.4', $data[1]['itemSeries'][3]['number']);
        self::assertEquals('Item 2.4', $data[1]['itemSeries'][3]['item']['libelle']);

        self::assertEquals('Num 2.5', $data[1]['itemSeries'][4]['number']);
        self::assertEquals('Item 2.5', $data[1]['itemSeries'][4]['item']['libelle']);

        self::assertEquals('Num 2.6', $data[1]['itemSeries'][5]['number']);
        self::assertEquals('Item 2.6', $data[1]['itemSeries'][5]['item']['libelle']);

        self::assertEquals('Num 2.7', $data[1]['itemSeries'][6]['number']);
        self::assertEquals('Item 2.7', $data[1]['itemSeries'][6]['item']['libelle']);

        self::assertEquals('Num 2.8', $data[1]['itemSeries'][7]['number']);
        self::assertEquals('Item 2.8', $data[1]['itemSeries'][7]['item']['libelle']);

        self::assertEquals('Num 2.9', $data[1]['itemSeries'][8]['number']);
        self::assertEquals('Item 2.9', $data[1]['itemSeries'][8]['item']['libelle']);

        self::assertEquals('Num 2.10', $data[1]['itemSeries'][9]['number']);
        self::assertEquals('Item 2.10', $data[1]['itemSeries'][9]['item']['libelle']);

        self::assertEquals('Num 2.11', $data[1]['itemSeries'][10]['number']);
        self::assertEquals('Item 2.11', $data[1]['itemSeries'][10]['item']['libelle']);

        self::assertEquals('Num 2.12', $data[1]['itemSeries'][11]['number']);
        self::assertEquals('Item 2.12', $data[1]['itemSeries'][11]['item']['libelle']);
    }
}
