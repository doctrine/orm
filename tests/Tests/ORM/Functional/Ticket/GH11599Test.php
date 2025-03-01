<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

use function count;

class GH11599Test extends OrmFunctionalTestCase
{
    private const ENTITIES = [
        GH11599File::class,
        GH11599ProductImage::class,
        GH11599Product::class,

        GH11599FileIgnoreEager::class,
        GH11599ProductImageIgnoreEager::class,
        GH11599ProductIgnoreEager::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema(self::ENTITIES);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach (self::ENTITIES as $entity) {
            $this->getEntityManager()->getRepository($entity)
                ->createQueryBuilder('s')->delete()->getQuery()->getResult();
        }
    }

    public function testEagerUsesForInheritanceAndFetchEagerOnOneToOneRelationAndFinalInheritanceWithNotOwing(): void
    {
        $em              = $this->getTestEntityManager();
        $productMetadata = $em->getClassMetadata(GH11599Product::class);
        $imageMapping    = $productMetadata->getAssociationMapping('image');
        self::assertFalse($imageMapping->isOwningSide());

        $image                  = new GH11599ProductImage();
        $image->someFieldForOne = 'test';
        $product                = new GH11599Product();
        $product->image         = $image;
        $image->product         = $product;

        $this->_em->persist($image);
        $this->_em->flush();
        $this->_em->persist($product);
        $this->_em->flush();
        $this->_em->clear();

        /** @var GH11599Product[] $entityOneArr */
        $entityOneArr = $this->_em->getRepository(GH11599Product::class)->findBy([]);
        $this->assertCount(1, $entityOneArr);
        $this->assertSame('test', $entityOneArr[0]->image->someFieldForOne);

        $log = $this->getQueryLog();
        $this->assertNotEmpty($log->queries);
        $last = $log->queries[count($log->queries) - 1];

        $this->assertSame(
            "SELECT t0.id AS id_1, t2.id AS id_3, t2.some_field_for_one AS some_field_for_one_4, t2.product_id AS product_id_5 FROM GH11599Product t0 LEFT JOIN one_to_one_single_table_with_eager t2 ON t2.product_id = t0.id AND t2.type = 'image'",
            $last['sql'],
        );
    }

    public function testEagerDoesNotUseForParentClasses(): void
    {
        $product                = new GH11599ProductIgnoreEager();
        $image                  = new GH11599ProductImageIgnoreEager();
        $image->someFieldForOne = 'test';
        $image->product         = $product;

        $this->_em->persist($image);
        $this->_em->persist($product);
        $this->_em->flush();
        $this->_em->clear();

        /** @var GH11599ProductIgnoreEager[] $entityOneArr */
        $entityOneArr = $this->_em->getRepository(GH11599ProductIgnoreEager::class)->findBy([]);
        $this->assertCount(1, $entityOneArr);
        $this->assertSame('test', $entityOneArr[0]->image->someFieldForOne);

        $log = $this->getQueryLog();
        $this->assertNotEmpty($log->queries);
        $last = $log->queries[count($log->queries) - 1];

        $this->assertSame(
            "SELECT t0.id AS id_1, t0.some_field_for_one AS some_field_for_one_2, t0.product_id AS product_id_3, t0.type FROM one_to_one_single_table_with_eager_ignore_eager t0 WHERE t0.product_id = ? AND t0.type IN ('image')",
            $last['sql'],
        );
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'one_to_one_single_table_with_eager')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    'image' => GH11599ProductImage::class,
])]
class GH11599File
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int $id;
}

#[ORM\Entity]
class GH11599ProductImage extends GH11599File
{
    #[ORM\OneToOne(
        targetEntity: GH11599Product::class,
        inversedBy: 'image',
        cascade: ['ALL'],
    )]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    public GH11599Product $product;

    #[ORM\Column(name: 'some_field_for_one', type: 'string', nullable: true)]
    public string|null $someFieldForOne = null;
}


#[ORM\Entity]
class GH11599Product
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int $id;

    #[ORM\OneToOne(
        targetEntity: GH11599ProductImage::class,
        mappedBy: 'product',
        cascade: ['ALL'],
        fetch: 'EAGER',
    )]
    public GH11599ProductImage|null $image = null;
}


#[ORM\Entity]
#[ORM\Table(name: 'one_to_one_single_table_with_eager_ignore_eager')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    'image' => GH11599ProductImageIgnoreEager::class,
])]
abstract class GH11599FileIgnoreEager
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int $id;

    #[ORM\OneToOne(
        targetEntity: GH11599ProductIgnoreEager::class,
    )]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    public GH11599ProductIgnoreEager $product;

    #[ORM\Column(name: 'some_field_for_one', type: 'string', nullable: true)]
    public string|null $someFieldForOne = null;
}


#[ORM\Entity]
class GH11599ProductImageIgnoreEager extends GH11599FileIgnoreEager
{
}


#[ORM\Entity]
class GH11599ProductIgnoreEager
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int $id;

    #[ORM\OneToOne(
        targetEntity: GH11599FileIgnoreEager::class,
        mappedBy: 'product',
        cascade: ['ALL'],
        fetch: 'EAGER',
    )]
    public GH11599FileIgnoreEager|null $image = null;
}
