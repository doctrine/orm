<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Query;
use Doctrine\Tests\Models\Company\CompanyManager;
use Doctrine\Tests\Models\Company\CompanyPerson;
use Doctrine\Tests\OrmFunctionalTestCase;

use function assert;

final class GH8443Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('company');

        parent::setUp();

        $this->createSchemaForModels(GH8443Foo::class);
    }

    /** @group GH-8443 */
    public function testJoinRootEntityWithForcePartialLoad(): void
    {
        $person = new CompanyPerson();
        $person->setName('John');

        $manager = new CompanyManager();
        $manager->setName('Adam');
        $manager->setSalary(1000);
        $manager->setDepartment('IT');
        $manager->setTitle('manager');

        $manager->setSpouse($person);

        $this->_em->persist($person);
        $this->_em->persist($manager);
        $this->_em->flush();
        $this->_em->clear();

        $manager = $this->_em->createQuery(
            "SELECT m from Doctrine\Tests\Models\Company\CompanyManager m
               JOIN m.spouse s
               WITH s.name = 'John'"
        )->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)->getSingleResult();
        $this->_em->refresh($manager);

        $this->assertEquals('John', $manager->getSpouse()->getName());
    }

    /** @group GH-8443 */
    public function testJoinRootEntityWithOnlyOneEntityInHierarchy(): void
    {
        $bar = new GH8443Foo('bar');

        $foo = new GH8443Foo('foo');
        $foo->setBar($bar);

        $this->_em->persist($bar);
        $this->_em->persist($foo);
        $this->_em->flush();
        $this->_em->clear();

        $foo = $this->_em->createQuery(
            'SELECT f from ' . GH8443Foo::class . " f JOIN f.bar b WITH b.name = 'bar'"
        )->getSingleResult();
        assert($foo instanceof GH8443Foo);

        $bar = $foo->getBar();
        assert($bar !== null);
        $this->assertEquals('bar', $bar->getName());
    }
}
/**
 * @Entity
 * @Table(name="GH2947_foo")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *      "foo" = "GH8443Foo"
 * })
 */
class GH8443Foo
{
    /**
     * @var int|null
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @var string
     * @Column
     */
    private $name;

    /**
     * @var GH8443Foo|null
     * @OneToOne(targetEntity="GH8443Foo")
     * @JoinColumn(name="bar_id", referencedColumnName="id")
     */
    private $bar;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setBar(GH8443Foo $bar): void
    {
        if ($bar !== $this->bar) {
            $this->bar      = $bar;
            $this->bar->bar = $this;
        }
    }

    public function getBar(): ?GH8443Foo
    {
        return $this->bar;
    }
}
