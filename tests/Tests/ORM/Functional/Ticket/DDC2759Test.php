<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-2759')]
class DDC2759Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            DDC2759Qualification::class,
            DDC2759Category::class,
            DDC2759QualificationMetadata::class,
            DDC2759MetadataCategory::class,
        );

        $qualification         = new DDC2759Qualification();
        $qualificationMetadata = new DDC2759QualificationMetadata($qualification);

        $category1 = new DDC2759Category();
        $category2 = new DDC2759Category();

        $metadataCategory1 = new DDC2759MetadataCategory($qualificationMetadata, $category1);
        $metadataCategory2 = new DDC2759MetadataCategory($qualificationMetadata, $category2);

        $this->_em->persist($qualification);
        $this->_em->persist($qualificationMetadata);

        $this->_em->persist($category1);
        $this->_em->persist($category2);

        $this->_em->persist($metadataCategory1);
        $this->_em->persist($metadataCategory2);

        $this->_em->flush();
        $this->_em->clear();
    }

    public function testCorrectNumberOfAssociationsIsReturned(): void
    {
        $repository = $this->_em->getRepository(DDC2759Qualification::class);

        $builder = $repository->createQueryBuilder('q')
            ->select('q, qm, qmc')
            ->innerJoin('q.metadata', 'qm')
            ->innerJoin('qm.metadataCategories', 'qmc');

        $result = $builder->getQuery()
            ->getArrayResult();

        self::assertCount(2, $result[0]['metadata']['metadataCategories']);
    }
}

#[Table(name: 'ddc_2759_qualification')]
#[Entity]
class DDC2759Qualification
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @var DDC2759QualificationMetadata */
    #[OneToOne(targetEntity: 'DDC2759QualificationMetadata', mappedBy: 'content')]
    public $metadata;
}

#[Table(name: 'ddc_2759_category')]
#[Entity]
class DDC2759Category
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @phpstan-var Collection<int, DDC2759MetadataCategory> */
    #[OneToMany(targetEntity: 'DDC2759MetadataCategory', mappedBy: 'category')]
    public $metadataCategories;
}

#[Table(name: 'ddc_2759_qualification_metadata')]
#[Entity]
class DDC2759QualificationMetadata
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @phpstan-var Collection<int, DDC2759MetadataCategory> */
    #[OneToMany(targetEntity: 'DDC2759MetadataCategory', mappedBy: 'metadata')]
    protected $metadataCategories;

    public function __construct(
        #[OneToOne(targetEntity: 'DDC2759Qualification', inversedBy: 'metadata')]
        public DDC2759Qualification $content,
    ) {
    }
}

#[Table(name: 'ddc_2759_metadata_category')]
#[Entity]
class DDC2759MetadataCategory
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    public function __construct(
        #[ManyToOne(targetEntity: 'DDC2759QualificationMetadata', inversedBy: 'metadataCategories')]
        public DDC2759QualificationMetadata $metadata,
        #[ManyToOne(targetEntity: 'DDC2759Category', inversedBy: 'metadataCategories')]
        public DDC2759Category $category,
    ) {
    }
}
