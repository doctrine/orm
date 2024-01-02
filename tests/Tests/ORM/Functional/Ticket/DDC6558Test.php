<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-6558')]
class DDC6558Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->_schemaTool->createSchema([
            $this->_em->getClassMetadata(DDC6558Person::class),
            $this->_em->getClassMetadata(DDC6558Employee::class),
            $this->_em->getClassMetadata(DDC6558Staff::class),
            $this->_em->getClassMetadata(DDC6558Developer::class),
            $this->_em->getClassMetadata(DDC6558Manager::class),
        ]);
    }

    public function testEmployeeIsPopulated(): void
    {
        $developer               = new DDC6558Developer();
        $developer->phoneNumber  = 1231231231;
        $developer->emailAddress = 'email@address.com';

        $this->_em->persist($developer);
        $this->_em->flush();
        $this->_em->clear();

        $persistedDeveloper = $this->_em->find(DDC6558Person::class, $developer->id);

        self::assertNotNull($persistedDeveloper->phoneNumber);
        self::assertNotNull($persistedDeveloper->emailAddress);
    }
}

#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['manager' => DDC6558Manager::class, 'staff' => DDC6558Staff::class, 'developer' => DDC6558Developer::class])]
abstract class DDC6558Person
{
    /** @var int */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public $id;
}

#[ORM\Entity]
class DDC6558Manager extends DDC6558Person
{
}

#[ORM\Entity]
abstract class DDC6558Employee extends DDC6558Person
{
    /** @var string */
    #[ORM\Column(type: 'string')]
    public $phoneNumber;
}

#[ORM\Entity]
class DDC6558Staff extends DDC6558Employee
{
}

#[ORM\Entity]
class DDC6558Developer extends DDC6558Employee
{
    /** @var string */
    #[ORM\Column(type: 'string')]
    public $emailAddress;
}
