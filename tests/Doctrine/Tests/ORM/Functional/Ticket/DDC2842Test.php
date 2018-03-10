<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;
use Doctrine\Tests\Models\Pagination\User;
use Doctrine\Tests\Models\Pagination\User1;

/**
 * @group DDC-2842
 */
class DDC2842Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('pagination');
        parent::setUp();
    }

    public function testSelectConditionSQL()
    {
        $this->_em->getRepository(User::class)->find(1);

        $this->assertSQLEquals(
            "SELECT t0.id AS id_1, t0.name AS name_2, t0.type, t0.email AS email_3 FROM pagination_user t0 WHERE t0.id = ?",
            $this->_sqlLoggerStack->queries[$this->_sqlLoggerStack->currentQuery]['sql']
        );

        $this->_em->getRepository(User1::class)->find(1);

        $this->assertSQLEquals(
            "SELECT t0.id AS id_1, t0.name AS name_2, t0.email AS email_3, t0.type FROM pagination_user t0 WHERE t0.id = ? AND t0.type IN ('user1')",
            $this->_sqlLoggerStack->queries[$this->_sqlLoggerStack->currentQuery]['sql']
        );
    }

    public function testSelectConditionCriteriaSQL()
    {
        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(DC2842UserEntity::class)
            ]
        );

        $user1 = new User1();
        $user1->name = 'user';
        $user1->email = 'user@email.com';

        $this->_em->persist($user1);

        $entity1 = new DC2842UserEntity();

        $entity1->setUser($user1);

        $this->_em->persist($entity1);

        $this->_em->flush();

        $this->_em->clear();

        $entity1 = $this->_em->getRepository(DC2842UserEntity::class)->find($entity1->getId());

        $user1 = $entity1->getUser();

        $this->assertSQLEquals(
            "SELECT t0.id AS id_1, t0.name AS name_2, t0.type, t0.email AS email_3 FROM pagination_user t0 WHERE t0.id = ?",
            $this->_sqlLoggerStack->queries[$this->_sqlLoggerStack->currentQuery]['sql']
        );

        $this->_em->remove($entity1);
        $this->_em->remove($user1);

        $this->_em->flush();
    }

    public function testSelectQuerySQL()
    {
        $query = $this->_em->createQuery('SELECT u FROM Doctrine\Tests\Models\Pagination\User u');
        $this->assertSQLEquals(
            $query->getSQL(),
            'select p0_.id as id_0, p0_.name as name_1, p0_.email as email_2, p0_.type as type_3 from pagination_user p0_'
        );
    }
}

/**
 * @Entity
 * @Table(name="user_entities")
 */
class DC2842UserEntity
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="Doctrine\Tests\Models\Pagination\User")
     */
    private $user;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }
}