<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH6141Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        Type::addType(GH6141PeopleType::NAME, GH6141PeopleType::class);

        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(GH6141Person::class),
                $this->_em->getClassMetadata(GH6141Boss::class),
                $this->_em->getClassMetadata(GH6141Employee::class),
            ]
        );
    }

    /**
     * The intent of this test is to ensure that the ORM is capable
     * of using objects as discriminators (which makes things a bit
     * more dynamic as you can see on the mapping of `GH6141Person`)
     *
     * @group 6141
     */
    public function testEnumDiscriminatorsShouldBeConvertedToString()
    {
        $boss = new GH6141Boss('John');
        $employee = new GH6141Employee('Bob');

        $this->_em->persist($boss);
        $this->_em->persist($employee);
        $this->_em->flush();
        $this->_em->clear();

        // Using DQL here to make sure that we'll use ObjectHydrator instead of SimpleObjectHydrator
        $query = $this->_em->createQueryBuilder()
            ->select('person')
            ->from(GH6141Person::class, 'person')
            ->where('person.name = :name')
            ->setMaxResults(1)
            ->getQuery();

        $query->setParameter('name', 'John');
        self::assertEquals($boss, $query->getOneOrNullResult(AbstractQuery::HYDRATE_OBJECT));
        self::assertEquals(
            GH6141People::get(GH6141People::BOSS),
            $query->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY)['discr']
        );

        $query->setParameter('name', 'Bob');
        self::assertEquals($employee, $query->getOneOrNullResult(AbstractQuery::HYDRATE_OBJECT));
        self::assertEquals(
            GH6141People::get(GH6141People::EMPLOYEE),
            $query->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY)['discr']
        );
    }
}

class GH6141PeopleType extends StringType
{
    const NAME = 'gh6141people';

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (!$value instanceof GH6141People) {
            $value = GH6141People::get($value);
        }

        return (string) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPhpValue($value, AbstractPlatform $platform)
    {
        return GH6141People::get($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}

class GH6141People
{
    const BOSS = 'boss';
    const EMPLOYEE = 'employee';

    /**
     * @var string
     */
    private $value;

    /**
     * @param string $value
     *
     * @return GH6141People
     *
     * @throws \InvalidArgumentException
     */
    public static function get($value)
    {
        if (!self::isValid($value)) {
            throw new \InvalidArgumentException();
        }

        return new self($value);
    }

    /**
     * @param string $valid
     *
     * @return bool
     */
    private static function isValid($valid)
    {
        return in_array($valid, [self::BOSS, self::EMPLOYEE]);
    }

    /**
     * @param string $value
     */
    private function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="gh6141people")
 * @DiscriminatorMap({
 *      Doctrine\Tests\ORM\Functional\Ticket\GH6141People::BOSS     = GH6141Boss::class,
 *      Doctrine\Tests\ORM\Functional\Ticket\GH6141People::EMPLOYEE = GH6141Employee::class
 * })
 */
abstract class GH6141Person
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Column(type="string")
     */
    public $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }
}

/** @Entity */
class GH6141Boss extends GH6141Person
{
}

/** @Entity */
class GH6141Employee extends GH6141Person
{
}
