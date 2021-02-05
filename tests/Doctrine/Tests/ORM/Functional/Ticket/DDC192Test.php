<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\OrmFunctionalTestCase;

use function assert;

/**
 * @group DDC-192
 */
class DDC192Test extends OrmFunctionalTestCase
{
    public function testSchemaCreation(): void
    {
        $classes = [
            $this->_em->getClassMetadata(DDC192User::class),
            $this->_em->getClassMetadata(DDC192Phonenumber::class),
        ];

        $this->_schemaTool->createSchema($classes);

        $tables = $this->_em->getConnection()
                            ->getSchemaManager()
                            ->listTableNames();

        foreach ($classes as $class) {
            assert($class instanceof ClassMetadata);
            self::assertContains($class->getTableName(), $tables);
        }
    }
}

/**
 * @Entity
 * @Table(name="ddc192_users")
 */
class DDC192User
{
    /**
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /** @Column(name="name", type="string") */
    public $name;
}


/**
 * @Entity
 * @Table(name="ddc192_phonenumbers")
 */
class DDC192Phonenumber
{
    /**
     * @Id
     * @Column(name="phone", type="string", length=40)
     */
    protected $phone;

    /**
     * @Id
     * @ManyToOne(targetEntity="DDC192User")
     * @JoinColumn(name="userId", referencedColumnName="id")
     */
    protected $User;

    public function setPhone($value): void
    {
        $this->phone = $value;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function setUser(User $user): void
    {
        $this->User = $user;
    }

    public function getUser()
    {
        return $this->User;
    }
}
