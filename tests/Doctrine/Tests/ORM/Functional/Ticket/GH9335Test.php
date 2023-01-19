<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\Tests\OrmFunctionalTestCase;

/** @group GH9335 */
final class GH9335Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! DBALType::hasType(GH9335IntObjectType::class)) {
            DBALType::addType(GH9335IntObjectType::class, GH9335IntObjectType::class);
        }

        $this->setUpEntitySchema([GH9335Book::class, GH9335Author::class]);
    }

    /**
     *  Verifies that entities with foreign keys with custom id object types don't throw an exception
     *
     * The test passes when refresh() does not throw an exception
     */
    public function testFlattenIdentifierWithObjectId(): void
    {
        $author = new GH9335Author('Douglas Adams');
        $book   = new GH9335Book(new GH9335IntObject(42), 'The Hitchhiker\'s Guide to the Galaxy', $author);

        $this->_em->persist($author);
        $this->_em->persist($book);
        $this->_em->flush();

        $this->_em->refresh($book);

        self::assertInstanceOf(GH9335IntObject::class, $book->getId());
    }
}


class GH9335IntObjectType extends Type
{
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getIntegerTypeDeclarationSQL($column);
    }

    public function getName(): string
    {
        return self::class;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): int
    {
        return $value->wrappedInt;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): GH9335IntObject
    {
        return new GH9335IntObject((int) $value);
    }

    public function getBindingType(): int
    {
        return ParameterType::INTEGER;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}

class GH9335IntObject
{
    /** @var int */
    public $wrappedInt;

    public function __construct(int $wrappedInt)
    {
        $this->wrappedInt = $wrappedInt;
    }

    public function __toString(): string
    {
        return (string) $this->wrappedInt;
    }
}

/** @Entity */
class GH9335Book
{
    /**
     * @var GH9335IntObject
     * @Id
     * @Column(type=GH9335IntObjectType::class, unique=true)
     */
    private $id;

    /**
     * @Column(type="string")
     * @var string
     */
    private $title;

    /**
     * @OneToOne(targetEntity="GH9335Author", mappedBy="book", cascade={"persist", "remove"})
     * @var GH9335Author
     */
    private $author;

    public function __construct(GH9335IntObject $id, string $title, ?GH9335Author $author = null)
    {
        $this->setId($id);
        $this->setTitle($title);
        $this->setAuthor($author);
    }

    public function getId(): ?GH9335IntObject
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle($title): void
    {
        $this->title = $title;
    }

    public function getAuthor(): ?GH9335Author
    {
        return $this->author;
    }

    public function setAuthor(?GH9335Author $author): self
    {
        $this->author = $author;

        // set the owning side of the relation
        if ($author) {
            $author->setBook($this);
        }

        return $this;
    }
}

/** @Entity */
class GH9335Author
{
    /**
     * @var GH9335Book
     * @Id
     * @OneToOne(targetEntity="GH9335Book", inversedBy="author")
     * @JoinColumn(name="book")
     */
    private $book;

    /**
     * @Column(type="string", nullable="true" )
     * @var string
     */
    private $name;

    public function __construct(?string $name)
    {
        $this->setName($name);
    }

    public function getBook(): ?GH9335Book
    {
        return $this->book;
    }

    public function setBook(GH9335Book $book): self
    {
        $this->book = $book;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
