<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\DbalTypes\CustomIdObject;
use Doctrine\Tests\OrmFunctionalTestCase;

use function method_exists;
use function str_replace;

/**
 * Functional tests for asserting that orphaned children in a OneToMany relationship get removed with a custom identifier
 *
 * @group GH10747
 */
final class GH10747Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! DBALType::hasType(GH10747CustomIdObjectHashType::class)) {
            DBALType::addType(GH10747CustomIdObjectHashType::class, GH10747CustomIdObjectHashType::class);
        }

        $this->setUpEntitySchema([GH10747Article::class, GH10747Credit::class]);
    }

    public function testOrphanedOneToManyDeletesCollection(): void
    {
        $object = new GH10747Article(
            new CustomIdObject('article')
        );

        $creditOne = new GH10747Credit(
            $object,
            'credit1'
        );

        $creditTwo = new GH10747Credit(
            $object,
            'credit2'
        );

        $object->setCredits(new ArrayCollection([$creditOne, $creditTwo]));

        $this->_em->persist($object);
        $this->_em->persist($creditOne);
        $this->_em->persist($creditTwo);
        $this->_em->flush();

        $id = $object->id;

        $object2 = $this->_em->find(GH10747Article::class, $id);

        $creditThree = new GH10747Credit(
            $object2,
            'credit3'
        );

        $object2->setCredits(new ArrayCollection([$creditThree]));

        $this->_em->persist($object2);
        $this->_em->persist($creditThree);
        $this->_em->flush();

        $currentDatabaseCredits = $this->_em->createQueryBuilder()
            ->select('c.id')
            ->from(GH10747Credit::class, 'c')
            ->getQuery()
            ->execute();

        self::assertCount(1, $currentDatabaseCredits);
    }
}

/**
 * @Entity
 * @Table
 */
class GH10747Article
{
    /**
     * @Id
     * @Column(type="Doctrine\Tests\ORM\Functional\Ticket\GH10747CustomIdObjectHashType")
     * @var CustomIdObject
     */
    public $id;

    /**
     * @ORM\OneToMany(targetEntity="GH10747Credit", mappedBy="article", orphanRemoval=true)
     *
     * @var Collection<int, GH10747Credit>
     */
    public $credits;

    public function __construct(CustomIdObject $id)
    {
        $this->id      = $id;
        $this->credits = new ArrayCollection();
    }

    public function setCredits(Collection $credits): void
    {
        $this->credits = $credits;
    }

    /** @return Collection<int, GH10747Credit> */
    public function getCredits(): Collection
    {
        return $this->credits;
    }
}

/**
 * @Entity
 * @Table
 */
class GH10747Credit
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue()
     *
     * @Id()
     * @var int|null
     */
    public $id = null;

    /** @var string */
    public $name;

    /**
     * @ORM\ManyToOne(targetEntity="GH10747Article", inversedBy="credits")
     *
     * @var GH10747Article
     */
    public $article;

    public function __construct(GH10747Article $article, string $name)
    {
        $this->article = $article;
        $this->name    = $name;
    }
}

class GH10747CustomIdObjectHashType extends DBALType
{
    /**
     * {@inheritDoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value->id . '_test';
    }

    /**
     * {@inheritDoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return new CustomIdObject(str_replace('_test', '', $value));
    }

    /**
     * {@inheritDoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        if (method_exists($platform, 'getStringTypeDeclarationSQL')) {
            return $platform->getStringTypeDeclarationSQL($fieldDeclaration);
        }

        return $platform->getVarcharTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::class;
    }
}
