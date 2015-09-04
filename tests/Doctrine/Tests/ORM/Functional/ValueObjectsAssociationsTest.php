<?php

namespace Doctrine\Tests\ORM\Functional;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;

/**
 *
 */
class ValueObjectsAssociationsTest extends \Doctrine\Tests\OrmFunctionalTestCase
{

    public function setUp()
    {
        parent::setUp();

        try {
            $classes = array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\EmbeddableManyToOneEntity'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\EmbeddableOneToManyEntity'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\EmbeddableManyToManyEntity'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\EmbeddableOneToOneEntity'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\BidirectionalManyToOneEntity'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\UnidirectionalManyToOneEntity'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\OneToManyEntity'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\UnidirectionalManyToManyEntity'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\BidirectionalManyToManyEntity'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\UnidirectionalOneToOneEntity'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\BidirectionalOneToOneEntity'),
            );
            $this->_schemaTool->dropSchema($classes);
            $this->_schemaTool->createSchema($classes);
        } catch(\Exception $e) {
        }
    }

    public function testMetadataHasReflectionEmbeddablesAccessible()
    {
        $classMetadata = $this->_em->getClassMetadata(__NAMESPACE__ . '\EmbeddableManyToOneEntity');
        $this->assertInstanceOf('Doctrine\Common\Reflection\RuntimePublicReflectionProperty', $classMetadata->getReflectionProperty('embed'));
        $this->assertInstanceOf('Doctrine\ORM\Mapping\ReflectionEmbeddedProperty', $classMetadata->getReflectionProperty('embed.unidirectional'));
        $this->assertInstanceOf('Doctrine\ORM\Mapping\ReflectionEmbeddedProperty', $classMetadata->getReflectionProperty('embed.bidirectional'));

        $classMetadata = $this->_em->getClassMetadata(__NAMESPACE__ . '\EmbeddableOneToManyEntity');
        $this->assertInstanceOf('Doctrine\Common\Reflection\RuntimePublicReflectionProperty', $classMetadata->getReflectionProperty('embed'));
        $this->assertInstanceOf('Doctrine\ORM\Mapping\ReflectionEmbeddedProperty', $classMetadata->getReflectionProperty('embed.entities'));

        $classMetadata = $this->_em->getClassMetadata(__NAMESPACE__ . '\EmbeddableManyToManyEntity');
        $this->assertInstanceOf('Doctrine\Common\Reflection\RuntimePublicReflectionProperty', $classMetadata->getReflectionProperty('embed'));
        $this->assertInstanceOf('Doctrine\ORM\Mapping\ReflectionEmbeddedProperty', $classMetadata->getReflectionProperty('embed.unidirectional'));
        $this->assertInstanceOf('Doctrine\ORM\Mapping\ReflectionEmbeddedProperty', $classMetadata->getReflectionProperty('embed.bidirectional'));
        $this->assertInstanceOf('Doctrine\ORM\Mapping\ReflectionEmbeddedProperty', $classMetadata->getReflectionProperty('embed.bidirectionalInversed'));

        $classMetadata = $this->_em->getClassMetadata(__NAMESPACE__ . '\EmbeddableOneToOneEntity');
        $this->assertInstanceOf('Doctrine\Common\Reflection\RuntimePublicReflectionProperty', $classMetadata->getReflectionProperty('embed'));
        $this->assertInstanceOf('Doctrine\ORM\Mapping\ReflectionEmbeddedProperty', $classMetadata->getReflectionProperty('embed.unidirectional'));
        $this->assertInstanceOf('Doctrine\ORM\Mapping\ReflectionEmbeddedProperty', $classMetadata->getReflectionProperty('embed.bidirectional'));
        $this->assertInstanceOf('Doctrine\ORM\Mapping\ReflectionEmbeddedProperty', $classMetadata->getReflectionProperty('embed.bidirectionalInversed'));
    }

    public function testCRUDManyToOne()
    {
        $relatedBidirectional = new BidirectionalManyToOneEntity();
        $relatedUnidirectional = new UnidirectionalManyToOneEntity();
        $this->_em->persist($relatedBidirectional);
        $this->_em->persist($relatedUnidirectional);

        $entity = new EmbeddableManyToOneEntity();
        $entity->embed->bidirectional = $relatedBidirectional;
        $entity->embed->unidirectional = $relatedUnidirectional;

        $this->_em->persist($entity);

        $this->_em->flush();

        $this->_em->clear();

        // 2. check loading value objects works
        $entity = $this->_em->find(EmbeddableManyToOneEntity::CLASSNAME, $entity->id);

        $this->assertInstanceOf(ManyToOneEmbeddable::CLASSNAME, $entity->embed);

        $this->assertInstanceOf(BidirectionalManyToOneEntity::CLASSNAME, $entity->embed->bidirectional);
        $this->assertEquals($relatedBidirectional->id, $entity->embed->bidirectional->id);

        $this->assertInstanceOf(UnidirectionalManyToOneEntity::CLASSNAME, $entity->embed->unidirectional);
        $this->assertEquals($relatedUnidirectional->id, $entity->embed->unidirectional->id);

        // 3. check changing value objects works
        $relatedBidirectional2 = new BidirectionalManyToOneEntity();
        $relatedUnidirectional2 = new UnidirectionalManyToOneEntity();
        $this->_em->persist($relatedBidirectional2);
        $this->_em->persist($relatedUnidirectional2);

        $entity->embed->bidirectional = $relatedBidirectional2;
        $entity->embed->unidirectional = $relatedUnidirectional2;

        $this->_em->flush();

        $this->_em->clear();

        $entity = $this->_em->find(EmbeddableManyToOneEntity::CLASSNAME, $entity->id);

        $this->assertInstanceOf(ManyToOneEmbeddable::CLASSNAME, $entity->embed);

        $this->assertInstanceOf(BidirectionalManyToOneEntity::CLASSNAME, $entity->embed->bidirectional);
        $this->assertEquals($relatedBidirectional2->id, $entity->embed->bidirectional->id);

        $this->assertInstanceOf(UnidirectionalManyToOneEntity::CLASSNAME, $entity->embed->unidirectional);
        $this->assertEquals($relatedUnidirectional2->id, $entity->embed->unidirectional->id);

        // 4. check deleting works
        $entityId = $entity->id;;
        $this->_em->remove($entity);
        $this->_em->flush();

        $this->assertNull($this->_em->find(EmbeddableManyToOneEntity::CLASSNAME, $entityId));
    }

    public function testCRUDOneToMany()
    {
        $related = array(
            new OneToManyEntity(),
            new OneToManyEntity(),
        );
        $this->_em->persist($related[0]);
        $this->_em->persist($related[1]);

        $entity = new EmbeddableOneToManyEntity();
        $entity->embed->entities = $related;
        $related[0]->property = $entity;
        $related[1]->property = $entity;

        $this->_em->persist($entity);

        $this->_em->flush();

        $this->_em->clear();

        // 2. check loading value objects works
        $entity = $this->_em->find(EmbeddableOneToManyEntity::CLASSNAME, $entity->id);

        $this->assertInstanceOf(OneToManyEmbeddable::CLASSNAME, $entity->embed);
        $this->assertInstanceOf(OneToManyEntity::CLASSNAME, $entity->embed->entities[0]);
        $this->assertInstanceOf(OneToManyEntity::CLASSNAME, $entity->embed->entities[1]);
        $this->assertEquals($related[0]->id, $entity->embed->entities[0]->id);
        $this->assertEquals($related[1]->id, $entity->embed->entities[1]->id);

        // 3. check changing value objects works
        $related2 = array(
            new OneToManyEntity(),
            new OneToManyEntity(),
        );
        $this->_em->persist($related2[0]);
        $this->_em->persist($related2[1]);

        $this->_em->remove($entity->embed->entities[0]);
        $this->_em->remove($entity->embed->entities[1]);

        $entity->embed->entities = $related2;
        $related2[0]->property = $entity;
        $related2[1]->property = $entity;

        $this->_em->flush();

        $this->_em->clear();

        $entity = $this->_em->find(EmbeddableOneToManyEntity::CLASSNAME, $entity->id);

        $this->assertInstanceOf(OneToManyEmbeddable::CLASSNAME, $entity->embed);
        $this->assertInstanceOf(OneToManyEntity::CLASSNAME, $entity->embed->entities[0]);
        $this->assertInstanceOf(OneToManyEntity::CLASSNAME, $entity->embed->entities[1]);
        $this->assertEquals($related2[0]->id, $entity->embed->entities[0]->id);
        $this->assertEquals($related2[1]->id, $entity->embed->entities[1]->id);

        // 4. check deleting works
        $entityId = $entity->id;;
        $this->_em->remove($entity);
        $this->_em->flush();

        $this->assertNull($this->_em->find(EmbeddableOneToManyEntity::CLASSNAME, $entityId));
    }

    public function testCRUDManyToMany()
    {
        $relatedUni = array(
            new UnidirectionalManyToManyEntity(),
            new UnidirectionalManyToManyEntity(),
        );

        $relatedBi = array(
            new BidirectionalManyToManyEntity(),
            new BidirectionalManyToManyEntity(),
        );

        $relatedBiInversed = array(
            new BidirectionalManyToManyEntity(),
            new BidirectionalManyToManyEntity(),
        );

        $this->_em->persist($relatedUni[0]);
        $this->_em->persist($relatedUni[1]);
        $this->_em->persist($relatedBi[0]);
        $this->_em->persist($relatedBi[1]);
        $this->_em->persist($relatedBiInversed[0]);
        $this->_em->persist($relatedBiInversed[1]);

        $entity = new EmbeddableManyToManyEntity();
        $entity->embed->unidirectional = $relatedUni;
        $entity->embed->bidirectional = $relatedBi;
        $entity->embed->bidirectionalInversed = $relatedBiInversed;
        $relatedBiInversed[0]->propertyInversed = array($entity);
        $relatedBiInversed[1]->propertyInversed = array($entity);

        $this->_em->persist($entity);

        $this->_em->flush();

        $this->_em->clear();

        // 2. check loading value objects works
        $entity = $this->_em->find(EmbeddableManyToManyEntity::CLASSNAME, $entity->id);

        $this->assertInstanceOf(ManyToManyEmbeddable::CLASSNAME, $entity->embed);
        $this->assertInstanceOf(UnidirectionalManyToManyEntity::CLASSNAME, $entity->embed->unidirectional[0]);
        $this->assertInstanceOf(UnidirectionalManyToManyEntity::CLASSNAME, $entity->embed->unidirectional[1]);
        $this->assertInstanceOf(BidirectionalManyToManyEntity::CLASSNAME, $entity->embed->bidirectional[0]);
        $this->assertInstanceOf(BidirectionalManyToManyEntity::CLASSNAME, $entity->embed->bidirectional[1]);
        $this->assertInstanceOf(BidirectionalManyToManyEntity::CLASSNAME, $entity->embed->bidirectionalInversed[0]);
        $this->assertInstanceOf(BidirectionalManyToManyEntity::CLASSNAME, $entity->embed->bidirectionalInversed[1]);
        $this->assertEquals($relatedUni[0]->id, $entity->embed->unidirectional[0]->id);
        $this->assertEquals($relatedUni[1]->id, $entity->embed->unidirectional[1]->id);
        $this->assertEquals($relatedBi[0]->id, $entity->embed->bidirectional[0]->id);
        $this->assertEquals($relatedBi[1]->id, $entity->embed->bidirectional[1]->id);
        $this->assertEquals($relatedBiInversed[0]->id, $entity->embed->bidirectionalInversed[0]->id);
        $this->assertEquals($relatedBiInversed[1]->id, $entity->embed->bidirectionalInversed[1]->id);

        // 3. check changing value objects works
        $relatedUni2 = array(
            new UnidirectionalManyToManyEntity(),
            new UnidirectionalManyToManyEntity(),
        );

        $relatedBi2 = array(
            new BidirectionalManyToManyEntity(),
            new BidirectionalManyToManyEntity(),
        );

        $relatedBiInversed2 = array(
            new BidirectionalManyToManyEntity(),
            new BidirectionalManyToManyEntity(),
        );

        $this->_em->persist($relatedUni2[0]);
        $this->_em->persist($relatedUni2[1]);
        $this->_em->persist($relatedBi2[0]);
        $this->_em->persist($relatedBi2[1]);
        $this->_em->persist($relatedBiInversed2[0]);
        $this->_em->persist($relatedBiInversed2[1]);

        $this->_em->remove($entity->embed->bidirectionalInversed[0]);
        $this->_em->remove($entity->embed->bidirectionalInversed[1]);

        $entity->embed->unidirectional = $relatedUni2;
        $entity->embed->bidirectional = $relatedBi2;
        $entity->embed->bidirectionalInversed = $relatedBiInversed2;
        $relatedBiInversed2[0]->propertyInversed = array($entity);
        $relatedBiInversed2[1]->propertyInversed = array($entity);

        $this->_em->flush();

        $this->_em->clear();

        $entity = $this->_em->find(EmbeddableManyToManyEntity::CLASSNAME, $entity->id);

        $this->assertInstanceOf(ManyToManyEmbeddable::CLASSNAME, $entity->embed);
        $this->assertInstanceOf(UnidirectionalManyToManyEntity::CLASSNAME, $entity->embed->unidirectional[0]);
        $this->assertInstanceOf(UnidirectionalManyToManyEntity::CLASSNAME, $entity->embed->unidirectional[1]);
        $this->assertInstanceOf(BidirectionalManyToManyEntity::CLASSNAME, $entity->embed->bidirectional[0]);
        $this->assertInstanceOf(BidirectionalManyToManyEntity::CLASSNAME, $entity->embed->bidirectional[1]);
        $this->assertInstanceOf(BidirectionalManyToManyEntity::CLASSNAME, $entity->embed->bidirectionalInversed[0]);
        $this->assertInstanceOf(BidirectionalManyToManyEntity::CLASSNAME, $entity->embed->bidirectionalInversed[1]);
        $this->assertEquals($relatedUni2[0]->id, $entity->embed->unidirectional[0]->id);
        $this->assertEquals($relatedUni2[1]->id, $entity->embed->unidirectional[1]->id);
        $this->assertEquals($relatedBi2[0]->id, $entity->embed->bidirectional[0]->id);
        $this->assertEquals($relatedBi2[1]->id, $entity->embed->bidirectional[1]->id);
        $this->assertEquals($relatedBiInversed2[0]->id, $entity->embed->bidirectionalInversed[0]->id);
        $this->assertEquals($relatedBiInversed2[1]->id, $entity->embed->bidirectionalInversed[1]->id);

        // 4. check deleting works
        $entityId = $entity->id;;
        $this->_em->remove($entity);
        $this->_em->flush();

        $this->assertNull($this->_em->find(EmbeddableManyToManyEntity::CLASSNAME, $entityId));
    }

    public function testCRUDOneToOne()
    {
        $relatedUni = new UnidirectionalOneToOneEntity();
        $relatedBi = new BidirectionalOneToOneEntity();
        $relatedBiInversed = new BidirectionalOneToOneEntity();
        $this->_em->persist($relatedUni);
        $this->_em->persist($relatedBi);
        $this->_em->persist($relatedBiInversed);

        $entity = new EmbeddableOneToOneEntity();
        $entity->embed->unidirectional = $relatedUni;
        $entity->embed->bidirectional = $relatedBi;
        $entity->embed->bidirectionalInversed = $relatedBiInversed;
        $relatedBiInversed->propertyInversed = $entity;

        $this->_em->persist($entity);

        $this->_em->flush();

        $this->_em->clear();

        // 2. check loading value objects works
        $entity = $this->_em->find(EmbeddableOneToOneEntity::CLASSNAME, $entity->id);

        $this->assertInstanceOf(OneToOneEmbeddable::CLASSNAME, $entity->embed);
        $this->assertInstanceOf(UnidirectionalOneToOneEntity::CLASSNAME, $entity->embed->unidirectional);
        $this->assertInstanceOf(BidirectionalOneToOneEntity::CLASSNAME, $entity->embed->bidirectional);
        $this->assertInstanceOf(BidirectionalOneToOneEntity::CLASSNAME, $entity->embed->bidirectionalInversed);
        $this->assertEquals($relatedUni->id, $entity->embed->unidirectional->id);
        $this->assertEquals($relatedBi->id, $entity->embed->bidirectional->id);
        $this->assertEquals($relatedBiInversed->id, $entity->embed->bidirectionalInversed->id);

        // 3. check changing value objects works
        $relatedUni2 = new UnidirectionalOneToOneEntity();
        $relatedBi2 = new BidirectionalOneToOneEntity();
        $relatedBiInversed2 = new BidirectionalOneToOneEntity();

        $this->_em->persist($relatedUni2);
        $this->_em->persist($relatedBi2);
        $this->_em->persist($relatedBiInversed2);

        $this->_em->remove($entity->embed->bidirectionalInversed);

        //FuckFuckFuck. Flush remove entity to database to avoid unique constraint violation, because inserts runs before delete in flush
        $this->_em->flush();

        $entity->embed->unidirectional = $relatedUni2;
        $entity->embed->bidirectional = $relatedBi2;
        $entity->embed->bidirectionalInversed = $relatedBiInversed2;
        $relatedBiInversed2->propertyInversed = $entity;

        $this->_em->flush();

        $this->_em->clear();

        $entity = $this->_em->find(EmbeddableOneToOneEntity::CLASSNAME, $entity->id);

        $this->assertInstanceOf(OneToOneEmbeddable::CLASSNAME, $entity->embed);
        $this->assertInstanceOf(UnidirectionalOneToOneEntity::CLASSNAME, $entity->embed->unidirectional);
        $this->assertInstanceOf(BidirectionalOneToOneEntity::CLASSNAME, $entity->embed->bidirectional);
        $this->assertInstanceOf(BidirectionalOneToOneEntity::CLASSNAME, $entity->embed->bidirectionalInversed);
        $this->assertEquals($relatedUni2->id, $entity->embed->unidirectional->id);
        $this->assertEquals($relatedBi2->id, $entity->embed->bidirectional->id);
        $this->assertEquals($relatedBiInversed2->id, $entity->embed->bidirectionalInversed->id);

        // 4. check deleting works
        $entityId = $entity->id;;
        $this->_em->remove($entity);
        $this->_em->flush();

        $this->assertNull($this->_em->find(EmbeddableOneToOneEntity::CLASSNAME, $entityId));
    }

    public function testLoadDqlManyToOne()
    {
        $relatedBidirectional = new BidirectionalManyToOneEntity();
        $relatedUnidirectional = new UnidirectionalManyToOneEntity();
        $this->_em->persist($relatedBidirectional);
        $this->_em->persist($relatedUnidirectional);

        $entities = [];

        $entities[0] = new EmbeddableManyToOneEntity();
        $entities[0]->embed->bidirectional = $relatedBidirectional;
        $entities[0]->embed->unidirectional = $relatedUnidirectional;

        $entities[1] = new EmbeddableManyToOneEntity();
        $entities[1]->embed->bidirectional = $relatedBidirectional;
        $entities[1]->embed->unidirectional = $relatedUnidirectional;

        $entities[2] = new EmbeddableManyToOneEntity();
        $entities[2]->embed->bidirectional = $relatedBidirectional;
        $entities[2]->embed->unidirectional = $relatedUnidirectional;

        $this->_em->persist($entities[0]);
        $this->_em->persist($entities[1]);
        $this->_em->persist($entities[2]);

        $this->_em->flush();
        $this->_em->clear();

        $dql = "
          SELECT p, unidirectional, bidirectional FROM " . __NAMESPACE__ . "\EmbeddableManyToOneEntity p
          INNER JOIN p.embed.unidirectional unidirectional
          INNER JOIN p.embed.bidirectional bidirectional
        ";

        $found = $this->_em->createQuery($dql)->getResult();

        $this->assertCount(3, $found);

        $this->assertEquals($entities[0]->embed->unidirectional->id, $found[0]->embed->unidirectional->id);
        $this->assertEquals($entities[0]->embed->bidirectional->id, $found[0]->embed->bidirectional->id);

        $this->assertEquals($entities[1]->embed->unidirectional->id, $found[1]->embed->unidirectional->id);
        $this->assertEquals($entities[1]->embed->bidirectional->id, $found[1]->embed->bidirectional->id);

        $this->assertEquals($entities[2]->embed->unidirectional->id, $found[2]->embed->unidirectional->id);
        $this->assertEquals($entities[2]->embed->bidirectional->id, $found[2]->embed->bidirectional->id);

        $found = $this->_em->createQuery($dql)->getArrayResult();

        $this->assertCount(3, $found);

        $this->assertEquals($entities[0]->embed->unidirectional->id, $found[0]['embed.unidirectional']['id']);
        $this->assertEquals($entities[0]->embed->bidirectional->id, $found[0]['embed.bidirectional']['id']);

        $this->assertEquals($entities[1]->embed->unidirectional->id, $found[1]['embed.unidirectional']['id']);
        $this->assertEquals($entities[1]->embed->bidirectional->id, $found[1]['embed.bidirectional']['id']);

        $this->assertEquals($entities[2]->embed->unidirectional->id, $found[2]['embed.unidirectional']['id']);
        $this->assertEquals($entities[2]->embed->bidirectional->id, $found[2]['embed.bidirectional']['id']);
    }

    public function testLoadDqlOneToMany()
    {
        $entities = [];
        $related = array(
            new OneToManyEntity(),
            new OneToManyEntity(),
        );
        $this->_em->persist($related[0]);
        $this->_em->persist($related[1]);

        $entities[0] = new EmbeddableOneToManyEntity();
        $entities[0]->embed->entities = $related;
        $related[0]->property = $entities[0];
        $related[1]->property = $entities[0];

        $related = array(
            new OneToManyEntity(),
            new OneToManyEntity(),
        );
        $this->_em->persist($related[0]);
        $this->_em->persist($related[1]);

        $entities[1] = new EmbeddableOneToManyEntity();
        $entities[1]->embed->entities = $related;
        $related[0]->property = $entities[1];
        $related[1]->property = $entities[1];

        $this->_em->persist($entities[0]);
        $this->_em->persist($entities[1]);

        $this->_em->flush();
        $this->_em->clear();

        $dql = "
          SELECT p, entities FROM " . __NAMESPACE__ . "\EmbeddableOneToManyEntity p
          INNER JOIN p.embed.entities entities
        ";

        $found = $this->_em->createQuery($dql)->getResult();

        $this->assertCount(2, $found);

        $this->assertEquals($entities[0]->embed->entities[0]->id, $found[0]->embed->entities[0]->id);
        $this->assertEquals($entities[0]->embed->entities[1]->id, $found[0]->embed->entities[1]->id);

        $this->assertEquals($entities[1]->embed->entities[0]->id, $found[1]->embed->entities[0]->id);
        $this->assertEquals($entities[1]->embed->entities[1]->id, $found[1]->embed->entities[1]->id);

        $found = $this->_em->createQuery($dql)->getArrayResult();

        $this->assertCount(2, $found);

        $this->assertEquals($entities[0]->embed->entities[0]->id, $found[0]['embed.entities'][0]['id']);
        $this->assertEquals($entities[0]->embed->entities[1]->id, $found[0]['embed.entities'][1]['id']);

        $this->assertEquals($entities[1]->embed->entities[0]->id, $found[1]['embed.entities'][0]['id']);
        $this->assertEquals($entities[1]->embed->entities[1]->id, $found[1]['embed.entities'][1]['id']);
    }

    public function testLoadDqlManyToMany()
    {
        $relatedUni = array(
            new UnidirectionalManyToManyEntity(),
            new UnidirectionalManyToManyEntity(),
        );

        $relatedBi = array(
            new BidirectionalManyToManyEntity(),
            new BidirectionalManyToManyEntity(),
        );

        $relatedBiInversed = array(
            new BidirectionalManyToManyEntity(),
            new BidirectionalManyToManyEntity(),
        );

        $this->_em->persist($relatedUni[0]);
        $this->_em->persist($relatedUni[1]);
        $this->_em->persist($relatedBi[0]);
        $this->_em->persist($relatedBi[1]);
        $this->_em->persist($relatedBiInversed[0]);
        $this->_em->persist($relatedBiInversed[1]);

        $entities[0] = new EmbeddableManyToManyEntity();
        $entities[0]->embed->unidirectional = $relatedUni;
        $entities[0]->embed->bidirectional = $relatedBi;
        $entities[0]->embed->bidirectionalInversed = $relatedBiInversed;
        $relatedBiInversed[0]->propertyInversed = array($entities[0]);
        $relatedBiInversed[1]->propertyInversed = array($entities[0]);

        $this->_em->persist($entities[0]);

        $relatedUni = array(
            new UnidirectionalManyToManyEntity(),
            new UnidirectionalManyToManyEntity(),
        );

        $relatedBi = array(
            new BidirectionalManyToManyEntity(),
            new BidirectionalManyToManyEntity(),
        );

        $relatedBiInversed = array(
            new BidirectionalManyToManyEntity(),
            new BidirectionalManyToManyEntity(),
        );

        $this->_em->persist($relatedUni[0]);
        $this->_em->persist($relatedUni[1]);
        $this->_em->persist($relatedBi[0]);
        $this->_em->persist($relatedBi[1]);
        $this->_em->persist($relatedBiInversed[0]);
        $this->_em->persist($relatedBiInversed[1]);

        $entities[1] = new EmbeddableManyToManyEntity();
        $entities[1]->embed->unidirectional = $relatedUni;
        $entities[1]->embed->bidirectional = $relatedBi;
        $entities[1]->embed->bidirectionalInversed = $relatedBiInversed;
        $relatedBiInversed[0]->propertyInversed = array($entities[1]);
        $relatedBiInversed[1]->propertyInversed = array($entities[1]);

        $this->_em->persist($entities[1]);

        $this->_em->flush();
        $this->_em->clear();

        $dql = "
          SELECT p, unidirectional, bidirectional, bidirectionalInversed FROM " . __NAMESPACE__ . "\EmbeddableManyToManyEntity p
          INNER JOIN p.embed.unidirectional unidirectional
          INNER JOIN p.embed.bidirectional bidirectional
          INNER JOIN p.embed.bidirectionalInversed bidirectionalInversed
        ";

        $found = $this->_em->createQuery($dql)->getResult();

        $this->assertCount(2, $found);

        $this->assertEquals($entities[0]->embed->unidirectional[0]->id, $found[0]->embed->unidirectional[0]->id);
        $this->assertEquals($entities[0]->embed->unidirectional[1]->id, $found[0]->embed->unidirectional[1]->id);
        $this->assertEquals($entities[0]->embed->bidirectional[0]->id, $found[0]->embed->bidirectional[0]->id);
        $this->assertEquals($entities[0]->embed->bidirectional[1]->id, $found[0]->embed->bidirectional[1]->id);
        $this->assertEquals($entities[0]->embed->bidirectionalInversed[0]->id, $found[0]->embed->bidirectionalInversed[0]->id);
        $this->assertEquals($entities[0]->embed->bidirectionalInversed[1]->id, $found[0]->embed->bidirectionalInversed[1]->id);

        $this->assertEquals($entities[1]->embed->unidirectional[0]->id, $found[1]->embed->unidirectional[0]->id);
        $this->assertEquals($entities[1]->embed->unidirectional[1]->id, $found[1]->embed->unidirectional[1]->id);
        $this->assertEquals($entities[1]->embed->bidirectional[0]->id, $found[1]->embed->bidirectional[0]->id);
        $this->assertEquals($entities[1]->embed->bidirectional[1]->id, $found[1]->embed->bidirectional[1]->id);
        $this->assertEquals($entities[1]->embed->bidirectionalInversed[0]->id, $found[1]->embed->bidirectionalInversed[0]->id);
        $this->assertEquals($entities[1]->embed->bidirectionalInversed[1]->id, $found[1]->embed->bidirectionalInversed[1]->id);

        $found = $this->_em->createQuery($dql)->getArrayResult();

        $this->assertCount(2, $found);

        $this->assertEquals($entities[0]->embed->unidirectional[0]->id, $found[0]['embed.unidirectional'][0]['id']);
        $this->assertEquals($entities[0]->embed->unidirectional[1]->id, $found[0]['embed.unidirectional'][1]['id']);
        $this->assertEquals($entities[0]->embed->bidirectional[0]->id, $found[0]['embed.bidirectional'][0]['id']);
        $this->assertEquals($entities[0]->embed->bidirectional[1]->id, $found[0]['embed.bidirectional'][1]['id']);
        $this->assertEquals($entities[0]->embed->bidirectionalInversed[0]->id, $found[0]['embed.bidirectionalInversed'][0]['id']);
        $this->assertEquals($entities[0]->embed->bidirectionalInversed[1]->id, $found[0]['embed.bidirectionalInversed'][1]['id']);

        $this->assertEquals($entities[1]->embed->unidirectional[0]->id, $found[1]['embed.unidirectional'][0]['id']);
        $this->assertEquals($entities[1]->embed->unidirectional[1]->id, $found[1]['embed.unidirectional'][1]['id']);
        $this->assertEquals($entities[1]->embed->bidirectional[0]->id, $found[1]['embed.bidirectional'][0]['id']);
        $this->assertEquals($entities[1]->embed->bidirectional[1]->id, $found[1]['embed.bidirectional'][1]['id']);
        $this->assertEquals($entities[1]->embed->bidirectionalInversed[0]->id, $found[1]['embed.bidirectionalInversed'][0]['id']);
        $this->assertEquals($entities[1]->embed->bidirectionalInversed[1]->id, $found[1]['embed.bidirectionalInversed'][1]['id']);
    }

    public function testLoadDqlOneToOne()
    {
        $relatedUni = new UnidirectionalOneToOneEntity();
        $relatedBi = new BidirectionalOneToOneEntity();
        $relatedBiInversed = new BidirectionalOneToOneEntity();
        $this->_em->persist($relatedUni);
        $this->_em->persist($relatedBi);
        $this->_em->persist($relatedBiInversed);

        $entities[0] = new EmbeddableOneToOneEntity();
        $entities[0]->embed->unidirectional = $relatedUni;
        $entities[0]->embed->bidirectional = $relatedBi;
        $entities[0]->embed->bidirectionalInversed = $relatedBiInversed;
        $relatedBiInversed->propertyInversed = $entities[0];

        $this->_em->persist($entities[0]);

        $relatedUni = new UnidirectionalOneToOneEntity();
        $relatedBi = new BidirectionalOneToOneEntity();
        $relatedBiInversed = new BidirectionalOneToOneEntity();
        $this->_em->persist($relatedUni);
        $this->_em->persist($relatedBi);
        $this->_em->persist($relatedBiInversed);

        $entities[1] = new EmbeddableOneToOneEntity();
        $entities[1]->embed->unidirectional = $relatedUni;
        $entities[1]->embed->bidirectional = $relatedBi;
        $entities[1]->embed->bidirectionalInversed = $relatedBiInversed;
        $relatedBiInversed->propertyInversed = $entities[1];

        $this->_em->persist($entities[1]);

        $this->_em->flush();
        $this->_em->clear();

        $dql = "
          SELECT p, unidirectional, bidirectional, bidirectionalInversed FROM " . __NAMESPACE__ . "\EmbeddableOneToOneEntity p
          INNER JOIN p.embed.unidirectional unidirectional
          INNER JOIN p.embed.bidirectional bidirectional
          INNER JOIN p.embed.bidirectionalInversed bidirectionalInversed
        ";

        $found = $this->_em->createQuery($dql)->getResult();

        $this->assertCount(2, $found);

        $this->assertEquals($entities[0]->embed->unidirectional->id, $found[0]->embed->unidirectional->id);
        $this->assertEquals($entities[0]->embed->bidirectional->id, $found[0]->embed->bidirectional->id);
        $this->assertEquals($entities[0]->embed->bidirectionalInversed->id, $found[0]->embed->bidirectionalInversed->id);

        $this->assertEquals($entities[1]->embed->unidirectional->id, $found[1]->embed->unidirectional->id);
        $this->assertEquals($entities[1]->embed->bidirectional->id, $found[1]->embed->bidirectional->id);
        $this->assertEquals($entities[1]->embed->bidirectionalInversed->id, $found[1]->embed->bidirectionalInversed->id);

        $found = $this->_em->createQuery($dql)->getArrayResult();

        $this->assertCount(2, $found);

        $this->assertEquals($entities[0]->embed->unidirectional->id, $found[0]['embed.unidirectional']['id']);
        $this->assertEquals($entities[0]->embed->bidirectional->id, $found[0]['embed.bidirectional']['id']);
        $this->assertEquals($entities[0]->embed->bidirectionalInversed->id, $found[0]['embed.bidirectionalInversed']['id']);

        $this->assertEquals($entities[1]->embed->unidirectional->id, $found[1]['embed.unidirectional']['id']);
        $this->assertEquals($entities[1]->embed->bidirectional->id, $found[1]['embed.bidirectional']['id']);
        $this->assertEquals($entities[1]->embed->bidirectionalInversed->id, $found[1]['embed.bidirectionalInversed']['id']);
    }

    /**
     * @group dql
     */
    public function testDqlOnEmbeddedObjectsManyToOne()
    {
        if ($this->isSecondLevelCacheEnabled) {
            $this->markTestSkipped('SLC does not work with UPDATE/DELETE queries through EM.');
        }

        $relatedBidirectional = new BidirectionalManyToOneEntity();
        $relatedUnidirectional = new UnidirectionalManyToOneEntity();
        $this->_em->persist($relatedBidirectional);
        $this->_em->persist($relatedUnidirectional);

        $entity = new EmbeddableManyToOneEntity();
        $entity->embed->bidirectional = $relatedBidirectional;
        $entity->embed->unidirectional = $relatedUnidirectional;

        $this->_em->persist($entity);
        $this->_em->flush();

        // SELECT
        $selectDql = "SELECT p FROM " . __NAMESPACE__ ."\\EmbeddableManyToOneEntity p WHERE p.embed.unidirectional = :relatedU AND p.embed.bidirectional = :relatedBi";
        $loadedEntity = $this->_em->createQuery($selectDql)
            ->setParameter('relatedU', $relatedUnidirectional->id)
            ->setParameter('relatedBi', $relatedBidirectional)
            ->getSingleResult();
        $this->assertEquals($entity, $loadedEntity);

        $this->assertNull(
            $this->_em->createQuery($selectDql)
                ->setParameter('relatedU', 42)
                ->setParameter('relatedBi', 100500)
                ->getOneOrNullResult()
        );

        $related2 = new BidirectionalManyToOneEntity();
        $this->_em->persist($related2);
        $this->_em->flush();

        // UPDATE
        $updateDql = "UPDATE " . __NAMESPACE__ . "\\EmbeddableManyToOneEntity p SET p.embed.bidirectional = :related WHERE p.embed.unidirectional = :relatedU";
        $this->_em->createQuery($updateDql)
            ->setParameter('relatedU', $relatedBidirectional)
            ->setParameter('related', $related2)
            ->execute();

        $this->_em->refresh($entity);

        $this->assertEquals($related2->id, $entity->embed->bidirectional->id);
        $this->assertEquals($relatedUnidirectional->id, $entity->embed->unidirectional->id);

        // DELETE
        $this->_em->createQuery("DELETE " . __NAMESPACE__ . "\\EmbeddableManyToOneEntity p WHERE p.embed.unidirectional = :relatedU AND p.embed.bidirectional = :relatedBi")
            ->setParameter('relatedU', $relatedUnidirectional)
            ->setParameter('relatedBi', $related2)
            ->execute();

        $this->_em->clear();
        $this->assertNull($this->_em->find(__NAMESPACE__.'\\EmbeddableManyToOneEntity', $entity->id));
    }

    /**
     * @group dql
     */
    public function testDqlOnEmbeddedObjectsOneToOne()
    {
        if ($this->isSecondLevelCacheEnabled) {
            $this->markTestSkipped('SLC does not work with UPDATE/DELETE queries through EM.');
        }

        $relatedBidirectional = new BidirectionalOneToOneEntity();
        $relatedUnidirectional = new UnidirectionalOneToOneEntity();
        $this->_em->persist($relatedBidirectional);
        $this->_em->persist($relatedUnidirectional);

        $entity = new EmbeddableOneToOneEntity();
        $entity->embed->bidirectional = $relatedBidirectional;
        $entity->embed->unidirectional = $relatedUnidirectional;

        $this->_em->persist($entity);
        $this->_em->flush();

        // SELECT
        $selectDql = "SELECT p FROM " . __NAMESPACE__ ."\\EmbeddableOneToOneEntity p WHERE p.embed.unidirectional = :relatedU AND p.embed.bidirectional = :relatedBi";
        $loadedEntity = $this->_em->createQuery($selectDql)
            ->setParameter('relatedU', $relatedUnidirectional->id)
            ->setParameter('relatedBi', $relatedBidirectional)
            ->getSingleResult();
        $this->assertEquals($entity, $loadedEntity);

        $this->assertNull(
            $this->_em->createQuery($selectDql)
                ->setParameter('relatedU', 42)
                ->setParameter('relatedBi', 100500)
                ->getOneOrNullResult()
        );

        $related2 = new BidirectionalOneToOneEntity();
        $this->_em->persist($related2);
        $this->_em->flush();

        // UPDATE
        $updateDql = "UPDATE " . __NAMESPACE__ . "\\EmbeddableOneToOneEntity p SET p.embed.bidirectional = :related WHERE p.embed.unidirectional = :relatedU";
        $this->_em->createQuery($updateDql)
            ->setParameter('relatedU', $relatedBidirectional)
            ->setParameter('related', $related2)
            ->execute();

        $this->_em->refresh($entity);

        $this->assertEquals($related2->id, $entity->embed->bidirectional->id);
        $this->assertEquals($relatedUnidirectional->id, $entity->embed->unidirectional->id);

        // DELETE
        $this->_em->createQuery("DELETE " . __NAMESPACE__ . "\\EmbeddableOneToOneEntity p WHERE p.embed.unidirectional = :relatedU AND p.embed.bidirectional = :relatedBi")
            ->setParameter('relatedU', $relatedUnidirectional)
            ->setParameter('relatedBi', $related2)
            ->execute();

        $this->_em->clear();
        $this->assertNull($this->_em->find(__NAMESPACE__.'\\EmbeddableOneToOneEntity', $entity->id));
    }

    public function testDqlWithNonExistentEmbeddableField()
    {
        $this->setExpectedException('Doctrine\ORM\Query\QueryException', 'no field or association named embed.asdfasdf');

        $this->_em->createQuery("SELECT p FROM " . __NAMESPACE__ . "\\EmbeddableManyToOneEntity p WHERE p.embed.asdfasdf IS NULL")
            ->execute();
    }

    public function testInlineEmbeddableWithPrefix()
    {
        $metadata = $this->_em->getClassMetadata(__NAMESPACE__ . '\EmbeddablePrefixesEntity');

        $this->assertEquals('some_prefix_manyToOne_id', $metadata->getAssociationMapping('embedPrefixed.manyToOne')['joinColumns'][0]['name']);
        $this->assertEquals('some_prefix_oneToOne_id', $metadata->getAssociationMapping('embedPrefixed.oneToOne')['joinColumns'][0]['name']);
    }

    public function testInlineEmbeddableEmptyPrefix()
    {
        $metadata = $this->_em->getClassMetadata(__NAMESPACE__ . '\EmbeddablePrefixesEntity');

        $this->assertEquals('embedDefault_manyToOne_id', $metadata->getAssociationMapping('embedDefault.manyToOne')['joinColumns'][0]['name']);
        $this->assertEquals('embedDefault_oneToOne_id', $metadata->getAssociationMapping('embedDefault.oneToOne')['joinColumns'][0]['name']);
    }

    public function testInlineEmbeddablePrefixFalse()
    {
        $metadata = $this->_em->getClassMetadata(__NAMESPACE__ . '\EmbeddablePrefixesEntity');

        $this->assertEquals('manyToOne_id', $metadata->getAssociationMapping('embedFalse.manyToOne')['joinColumns'][0]['name']);
        $this->assertEquals('oneToOne_id', $metadata->getAssociationMapping('embedFalse.oneToOne')['joinColumns'][0]['name']);
    }
}

/**
 * @Embeddable()
 */
class EmbedPrefixes
{
    /**
     * @ManyToOne(targetEntity="UnidirectionalManyToOneEntity")
     */
    public $manyToOne;

    /**
     * @OneToOne(targetEntity="UnidirectionalOneToOneEntity")
     */
    public $oneToOne;
}

/**
 * @Entity
 */
class EmbeddablePrefixesEntity
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var int
     * @Embedded(class="EmbedPrefixes")
     */
    public $embedDefault;

    /**
     * @var int
     * @Embedded(class="EmbedPrefixes", columnPrefix=false)
     */
    public $embedFalse;

    /**
     * @var int
     * @Embedded(class="EmbedPrefixes", columnPrefix="some_prefix_")
     */
    public $embedPrefixed;
}

/**
 * @Entity
 */
class BidirectionalManyToOneEntity
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var int
     * @OneToMany(targetEntity="EmbeddableManyToOneEntity", mappedBy="embed.bidirectional")
     */
    public $property;
}

/**
 * @Entity
 */
class UnidirectionalManyToOneEntity
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
}

/**
 * @Embeddable
 */
class ManyToOneEmbeddable
{
    const CLASSNAME = __CLASS__;

    /**
     * @ManyToOne(targetEntity="UnidirectionalManyToOneEntity")
     */
    public $unidirectional;

    /**
     * @ManyToOne(targetEntity = "BidirectionalManyToOneEntity", inversedBy = "property")
     */
    public $bidirectional;
}

/**
 * @Entity
 */
class EmbeddableManyToOneEntity
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Embedded(class = "ManyToOneEmbeddable", columnPrefix = false)
     */
    public $embed;

    public function __construct()
    {
        $this->embed = new ManyToOneEmbeddable();
    }
}

/**
 * @Entity
 */
class OneToManyEntity
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id()
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var int
     * @ManyToOne(targetEntity="EmbeddableOneToManyEntity", inversedBy="embed.entities")
     */
    public $property;
}


/**
 * @Embeddable()
 */
class OneToManyEmbeddable
{
    const CLASSNAME = __CLASS__;

    /**
     * @OneToMany(targetEntity="OneToManyEntity", mappedBy="property")
     */
    public $entities;
}

/**
 * @Entity
 */
class EmbeddableOneToManyEntity
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Embedded(class = "OneToManyEmbeddable", columnPrefix = false)
     */
    public $embed;

    public function __construct()
    {
        $this->embed = new OneToManyEmbeddable();
    }
}

/**
 * @Entity
 */
class UnidirectionalManyToManyEntity
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id()
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
}

/**
 * @Entity
 */
class BidirectionalManyToManyEntity
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id()
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var int
     * @ManyToMany(targetEntity="EmbeddableManyToManyEntity", mappedBy="embed.bidirectional")
     */
    public $property;

    /**
     * @var int
     * @ManyToMany(targetEntity="EmbeddableManyToManyEntity", inversedBy="embed.bidirectionalInversed")
     */
    public $propertyInversed;
}

/**
 * @Embeddable()
 */
class ManyToManyEmbeddable
{
    const CLASSNAME = __CLASS__;

    /**
     * @ManyToMany(targetEntity="UnidirectionalManyToManyEntity")
     */
    public $unidirectional;

    /**
     * @ManyToMany(targetEntity="BidirectionalManyToManyEntity", inversedBy="property")
     */
    public $bidirectional;

    /**
     * @ManyToMany(targetEntity="BidirectionalManyToManyEntity", mappedBy="propertyInversed")
     */
    public $bidirectionalInversed;
}

/**
 * @Entity
 */
class EmbeddableManyToManyEntity
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Embedded(class = "ManyToManyEmbeddable", columnPrefix = false)
     */
    public $embed;

    /**
     * DDC3480Vacancy constructor.
     */
    public function __construct()
    {
        $this->embed = new ManyToManyEmbeddable();
    }
}

/**
 * @Entity
 */
class UnidirectionalOneToOneEntity
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id()
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
}

/**
 * @Entity
 */
class BidirectionalOneToOneEntity
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id()
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var int
     * @OneToOne(targetEntity="EmbeddableOneToOneEntity", mappedBy="embed.bidirectional")
     */
    public $property;

    /**
     * @var int
     * @OneToOne(targetEntity="EmbeddableOneToOneEntity", inversedBy="embed.bidirectionalInversed")
     */
    public $propertyInversed;
}

/**
 * @Embeddable()
 */
class OneToOneEmbeddable
{
    const CLASSNAME = __CLASS__;

    /**
     * @OneToOne(targetEntity="UnidirectionalOneToOneEntity")
     */
    public $unidirectional;

    /**
     * @OneToOne(targetEntity="BidirectionalOneToOneEntity", inversedBy="property")
     */
    public $bidirectional;

    /**
     * @OneToOne(targetEntity="BidirectionalOneToOneEntity", mappedBy="propertyInversed")
     */
    public $bidirectionalInversed;
}

/**
 * @Entity
 */
class EmbeddableOneToOneEntity
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Embedded(class = "OneToOneEmbeddable", columnPrefix = false)
     */
    public $embed;

    /**
     * DDC3480Vacancy constructor.
     */
    public function __construct()
    {
        $this->embed = new OneToOneEmbeddable();
    }
}
